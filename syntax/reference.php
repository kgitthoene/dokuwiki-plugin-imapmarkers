<?php
/**
 * DokuWiki Plugin imapmarkers (Syntax Component)
 *
 * @license MIT https://en.wikipedia.org/wiki/MIT_License
 * @author  Kai Thoene <k.git.thoene@gmx.net>
 */
class syntax_plugin_imapmarkers_reference extends \dokuwiki\Extension\SyntaxPlugin {
  private const MATCH_IS_UNKNOWN = 0;
  private const MATCH_IS_AREA = 1;
  private const MATCH_IS_CONFIG = 2;
  private const MATCH_IS_LOCATION = 3;

  private bool $is_debug;
  private string $component;

  function __construct() {
    $this->is_debug = false;
    global $ID;
    $this->component = sprintf("plugin_%s_%s", $this->getPluginName(), $this->getPluginComponent());
    if ($this->is_debug) {
      dbglog(sprintf("syntax_plugin_imapmarkers_reference.__construct ID='%s' COMPONENT='%s'", cleanID($ID), $this->getPluginComponent()));
    }
  }

  //function getType(){ return 'substition';}
  function getType() {
    return 'formatting';
  }
  function getAllowedTypes() {
    return array('formatting', 'substition', 'disabled');
  }
  function getPType() {
    return 'normal';
  }
  function getSort() {
    return 184;
  }
  // override default accepts() method to allow nesting - ie, to get the plugin accepts its own entry syntax
  function accepts($mode) {
    if ($mode == substr(get_class($this), 7))
      return true;
    return parent::accepts($mode);
  }

  /**
   * Connect pattern to lexer
   */
  function connectTo($mode) {
    $this->Lexer->addSpecialPattern('\{{2}(?i)IMAPMLOC>.+?\}{2}', $mode, $this->component);
  }

  /**
   * Handle the match
   */
  function handle($match, $state, $pos, Doku_Handler $handler) {
    $args = array($state);
    switch ($state) {
      case DOKU_LEXER_SPECIAL:
        // check for marker location:
        $is_correct = false;
        $err_msg = "";
        $matches = array();
        $match = trim($match);
        if ($this->is_debug) {
          dbglog(sprintf("syntax_plugin_imapmarkers_reference.handle MATCH='%s' COMPONENT='%s'", $match, $this->getPluginComponent()));
        }
        if (preg_match("/\{{2}(?i)IMAPMLOC>\s*(.+?)\s*\|\s*(.+?)\s*\}{2}/", $match, $matches)) {
          $loc_id = $matches[1];
          $loc_title = $matches[2];
          $is_correct = true;
          $args = array($state, self::MATCH_IS_LOCATION, $is_correct, $err_msg, $loc_id, $loc_title);
        } else {
          $err_msg = sprintf("Malformed location! LOCATION='%s'", $match);
          $args = array($state, self::MATCH_IS_UNKNOWN, $is_correct, $err_msg);
        }
        if ($this->is_debug) {
          dbglog(sprintf("syntax_plugin_imapmarkers_reference.handle::DOKU_LEXER_SPECIAL: [%d] MATCH='%s'", $this->nr_imagemap_handler, $match));
        }
        break;
      case DOKU_LEXER_UNMATCHED:
        $handler->_addCall('cdata', array($match), $pos);
        return false;
    }
    return $args;
  }

  /**
   * Create output
   */
  function render($mode, Doku_Renderer $renderer, $data) {
    if ($mode == 'xhtml') {
      $state = $data[0];
      static $has_content = false;
      switch ($state) {
        case DOKU_LEXER_SPECIAL:
          if ($this->is_debug) {
            dbglog(sprintf("syntax_plugin_imapmarkers.render::DOKU_LEXER_SPECIAL: [%d] DATA='%s'", $this->nr_imagemap_render, implode(", ", $data)));
          }
          $match_type = self::MATCH_IS_UNKNOWN;
          $is_correct = false;
          $err_msg = "";
          list($state, $match_type, $is_correct, $err_msg) = $data;
          if ($is_correct) {
            switch ($match_type) {
              case self::MATCH_IS_LOCATION:
                list($state, $match_type, $is_correct, $err_msg, $loc_id, $loc_title) = $data;
                $renderer->doc .= sprintf('<span class="imapmarkers imapmarkers-location" location_id="%s">%s</span>', $loc_id, $loc_title);
                break;
            }
          } else {
            $renderer->doc .= sprintf('  <br /><span style="color:white; background-color:red;">ERROR -- %s</span>%s', $err_msg, DOKU_LF);
          }
          break;
      }
    }
    return true;
  } // public function render
}