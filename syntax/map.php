<?php
/**
 * DokuWiki Plugin imapmarkers (Syntax Component)
 *
 * @license MIT https://en.wikipedia.org/wiki/MIT_License
 * @author  Kai Thoene <k.git.thoene@gmx.net>
 */
if (!defined('DOKU_INC')) {
  die();
}

#declare(strict_types=1);

use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\Logger;

include(dirname(__FILE__) . "/imapmarkers_simple_html_dom.php");


class syntax_plugin_imapmarkers_map extends \dokuwiki\Extension\SyntaxPlugin {
  private const MATCH_IS_UNKNOWN = 0;
  private const MATCH_IS_AREA = 1;
  private const MATCH_IS_CONFIG = 2;
  private const MATCH_IS_LOCATION = 3;

  private int $nr_imagemap_handler;
  private int $nr_imagemap_render;
  private array $a_areas;
  private array $a_cfg;
  private bool $is_debug;
  private string $component;

  function __construct() {
    $this->is_debug = false;
    global $ID;
    if ($this->is_debug) {
      dbglog(sprintf("syntax_plugin_imapmarkers_map.__construct ID='%s' PLUGIN='%s'", cleanID($ID), $this->getPluginName()));
    }
    $this->nr_imagemap_handler = -1;
    $this->nr_imagemap_render = -1;
    $this->a_areas = array();
    $this->a_cfg = array();
    $this->component = sprintf("plugin_%s_%s", $this->getPluginName(), $this->getPluginComponent());
  }

  public function getType() {
    return 'container';
  }
  public function getPType() {
    return 'block';
  }
  public function getSort() {
    return 185;
  }
  public function getAllowedTypes() {
    return array('formatting', 'substition', 'disabled', 'protected', 'container', 'paragraphs');
  }

  /**
   * Connect pattern to lexer
   */
  public function connectTo($mode) {
    if ($mode == "base") {
      $this->Lexer->addEntryPattern('\{{2}(?i)IMAPMARKERS>[^\}]+\}{2}', $mode, $this->component);
      $this->Lexer->addPattern('\s*\{{2}(?i)CFG>\}{2}.*?\{{2}<CFG\s*\}{2}\s*', $this->component);
      $this->Lexer->addPattern('\s*\[{2}.+?\]{2}\s*', $this->component);
    }
  }

  /**
   * Connect exit pattern to lexer
   */
  public function postConnect() {
    $this->Lexer->addExitPattern('\{{2}<(?i)IMAPMARKERS\}{2}', $this->component);
  }

  /**
   * Handle the match
   */
  public function handle($match, $state, $pos, Doku_Handler $handler) {
    global $conf;
    global $ID;
    $args = array($state);
    switch ($state) {
      case DOKU_LEXER_ENTER:
        $this->nr_imagemap_handler++;
        if ($this->is_debug) {
          dbglog(sprintf("syntax_plugin_imapmarkers_map.handle::DOKU_LEXER_ENTER: [%d] MATCH='%s' HANDLER='%s'", $this->nr_imagemap_handler, $match, substr($match, 14, -2)));
        }
        $img = Doku_Handler_Parse_Media(substr($match, 14, -2));
        if ($this->is_debug) {
          dbglog(sprintf("syntax_plugin_imapmarkers_map.handle::DOKU_LEXER_ENTER: [%d] IMG='%s'", $this->nr_imagemap_handler, $img));
        }
        $args = array($state, $img['type'], $img['src'], $img['title'], $img['align'], $img['width'], $img['height'], $img['cache']);
        if ($this->is_debug) {
          dbglog(sprintf("syntax_plugin_imapmarkers_map.handle::DOKU_LEXER_ENTER: ARGS=[ %s ]"), implode(", ", $args));
        }
        break;
      case DOKU_LEXER_MATCHED:
        $is_correct = false;
        $is_match_ok = false;
        $err_msg = "";
        $matches = array();
        $match = trim($match);
        if ($this->is_debug) {
          dbglog(sprintf("syntax_plugin_imapmarkers_map.handle::DOKU_LEXER_MATCHED: [%d] MATCH='%s' POS=%s", $this->nr_imagemap_handler, $match, $pos));
        }
        //----------
        // check for area with or without identifier:
        if (preg_match("/\[{2}\s*([^|]*?)\s*\|\s*([^|]*?)\s*\|\s*([^|]*?)\s*@\s*([\d,\s]+)\s*\]{2}/", $match, $matches)
          or preg_match("/\[{2}\s*([^|]*?)\s*\|\s*([^|]*?)\s*@\s*([\d,\s]+)\s*\]{2}/", $match, $matches)) {
          switch (count($matches)) {
            case 5: // with identifier.
              $link = $matches[1];
              $loc_id = $matches[2];
              $text = $matches[3];
              $coordinates = $matches[4];
              $is_match_ok = true;
              break;
            case 4: // without identifier.
              $link = $matches[1];
              $loc_id = "";
              $text = $matches[2];
              $coordinates = $matches[3];
              $is_match_ok = true;
              break;
            default:
              $err_msg = sprintf("Invalid area! AREA='%s'", $match);
          }
          if ($is_match_ok) {
            $a_coords = explode(",", $coordinates);
            foreach ($a_coords as $key => $value) {
              $a_coords[$key] = intval(trim($value));
            }
            switch (count($a_coords)) {
              case 3:
              case 4:
              case 6:
                $is_correct = true;
                break;
              default:
                if ((count($a_coords) >= 6) and ((count($a_coords) % 2) == 0)) {
                  $is_correct = true;
                  break;
                }
                $err_msg = sprintf("Invalid number of coordinates! COUNT=%d", count($a_coords));
            }
            $uri = $link;
            $classes = "";
            if ($link != "") {
              // analyse link.
              $dokuwiki_link = sprintf("[[%s|%s]]", $link, $text);
              $rendered_result = $this->render_text($dokuwiki_link);
              $dom = imapmarkers\str_get_html($rendered_result);
              $a = $dom->find('a', 0);
              $uri = $a->href;
              $classes = $a->class;
            }
            if ($this->is_debug) {
              dbglog(sprintf("syntax_plugin_imapmarkers_map.handle::DOKU_LEXER_MATCHED: URL='%s' CLASS='%s'", $uri, $classes));
            }
            $args = array($state, self::MATCH_IS_AREA, $is_correct, $err_msg, $link, $loc_id, $text, $a_coords, $uri, $classes);
          }
          break;
        } else {
          if (preg_match("/^\{{2}(?i)CFG>\}{2}\s*(.*?)\s*\{{2}<CFG\s*\}{2}$/s", $match, $matches)) {
            if (count($matches) == 2) {
              $cfg = $matches[1];
              // test JSON from configuration:
              if (json_decode($cfg)) {
                $is_correct = true;
                $args = array($state, self::MATCH_IS_CONFIG, $is_correct, $err_msg, $cfg);
                break;
              } else {
                $err_msg = sprintf("Invalid JSON in configuration! JSON='%s'", $cfg);
              }
            } else {
              $err_msg = sprintf("Invalid configuration! CONFIG='%s'", $match);
            }

          } else {
            $err_msg = sprintf("Invalid expression! EXPRESSION='%s'", $match);
          }
        }
        $args = array($state, self::MATCH_IS_UNKNOWN, $is_correct, $err_msg);
        break;
    }
    return $args;
  } // public function handle

  /**
   * Create output
   */
  public function render($mode, Doku_Renderer $renderer, $data) {
    if ($mode == 'xhtml') {
      global $conf;
      global $ID;
      $state = $data[0];
      static $has_content = false;
      switch ($state) {
        case DOKU_LEXER_ENTER:
          $this->nr_imagemap_render++;
          if ($this->is_debug) {
            dbglog(sprintf("syntax_plugin_imapmarkers_map.render::DOKU_LEXER_ENTER: [%d] DATA='%s'", $this->nr_imagemap_render, implode($data, ", ")));
          }
          list($state, $type, $src, $title, $align, $width, $height, $cache) = $data;
          if ($type == 'internalmedia') {
            $exists = null;
            resolve_mediaid(getNS($ID), $src, $exists);
          }
          $renderer->doc .= sprintf('<p id="imapmarkers-container-%d" class="imapmarkers imapmarkers-container">%s', $this->nr_imagemap_render, DOKU_LF);
          $src = ml($src, array('w' => $width, 'h' => $height, 'cache' => $cache));
          $renderer->doc .= sprintf('  <img src="%s" id="imapmarkers-img-%d" class="imapmarkers imapmarkers-image media%s imap" usemap="#imapmarkers-map-%d"', $src, $this->nr_imagemap_render, $align, $this->nr_imagemap_render);
          if ($align == 'right' || $align == 'left')
            $renderer->doc .= sprintf(' align="%s"', $align);
          if (!is_null($title)) {
            $title = $renderer->_xmlEntities($title);
            $renderer->doc .= sprintf(' title="%s" alt="%s"', $title, $title);
          } else {
            $renderer->doc .= ' alt=""';
          }
          if (!is_null($width))
            $renderer->doc .= sprintf(' width="%s"', $renderer->_xmlEntities($width));
          if (!is_null($height))
            $renderer->doc .= sprintf(' height="%s"', $renderer->_xmlEntities($height));
          $renderer->doc .= sprintf(' />%s', DOKU_LF);
          $renderer->doc .= sprintf('</p>%s', DOKU_LF);
          break;
        case DOKU_LEXER_MATCHED:
          $match_type = self::MATCH_IS_UNKNOWN;
          $is_correct = false;
          $err_msg = "";
          list($state, $match_type, $is_correct, $err_msg) = $data;
          if ($is_correct) {
            switch ($match_type) {
              case self::MATCH_IS_AREA:
                if (!array_key_exists($this->nr_imagemap_render, $this->a_areas)) {
                  $this->a_areas[$this->nr_imagemap_render] = array();
                }
                array_push($this->a_areas[$this->nr_imagemap_render], $data);
                break;
              case self::MATCH_IS_CONFIG:
                if (!array_key_exists($this->nr_imagemap_render, $this->a_cfg)) {
                  $this->a_cfg[$this->nr_imagemap_render] = array();
                }
                array_push($this->a_cfg[$this->nr_imagemap_render], $data);
                break;
            }
            if ($this->is_debug) {
              dbglog(sprintf("syntax_plugin_imapmarkers_map.render::DOKU_LEXER_MATCHED: [%d] DATA='%s'", $this->nr_imagemap_render, implode($data, ", ")));
            }
          } else {
            $renderer->doc .= sprintf('  <br /><span style="color:white; background-color:red;">ERROR -- %s</span>%s', $err_msg, DOKU_LF);
          }
          break;
        case DOKU_LEXER_UNMATCHED:
          if ($this->is_debug) {
            dbglog(sprintf("syntax_plugin_imapmarkers_map.render::DOKU_LEXER_UNMATCHED: [%d] DATA='%s'", $this->nr_imagemap_render, implode($data, ", ")));
          }
          break;
        case DOKU_LEXER_EXIT:
          $is_all_ok = true;
          $err_msg = "";
          $nr_areas = 0;
          $nr_cfgs = 0;
          if (array_key_exists($this->nr_imagemap_render, $this->a_areas)) {
            $nr_areas = count($this->a_areas[$this->nr_imagemap_render]);
          }
          if (array_key_exists($this->nr_imagemap_render, $this->a_cfg)) {
            $nr_cfgs = 1;
          }
          if ($this->is_debug) {
            dbglog(sprintf("syntax_plugin_imapmarkers_map.render::DOKU_LEXER_EXIT: [%d] DATA='%s' #AREAS=%d #CFGS=%d", $this->nr_imagemap_render, implode($data, ", "), $nr_areas, $nr_cfgs));
          }
          if ($nr_areas > 0) {
            foreach ($this->a_areas[$this->nr_imagemap_render] as $value) {
              list($state, $match_type, $is_correct, $err_msg) = $value;
              if (!$is_correct) {
                $renderer->doc .= sprintf('  <br /><span style="color:white; background-color:red;">ERROR -- %s</span>%s', $err_msg, DOKU_LF);
                $is_all_ok = false;
              }
            }
            if ($is_all_ok) {
              $renderer->doc .= sprintf('  <map name="imapmarkers-map-%d" class="imapmarkers imapmarkers-map">%s', $this->nr_imagemap_render, DOKU_LF);
              foreach ($this->a_areas[$this->nr_imagemap_render] as $key => $value) {
                list($state, $match_type, $is_correct, $err_msg, $link, $loc_id, $text, $a_coords, $uri, $classes) = $value;
                $link = ($link == "") ? "#" : $link;
                $shape = "";
                switch (count($a_coords)) {
                  case 3:
                    $shape = "circle";
                    break;
                  case 4:
                    $shape = "rect";
                    break;
                  default:
                    $shape = "poly";
                }
                $renderer->doc .= sprintf('    <area id="imapmarkers-area-%d-%d" location_id="%s" class="imapmarkers" shape="%s" coords="%s" alt="%s" title="%s" href="%s" />%s', $this->nr_imagemap_render, $key, $loc_id, $shape, implode($a_coords, ","), $text, $text, $uri, DOKU_LF);
              }
              $renderer->doc .= sprintf('    <div style="display:none;" class="imapcontent">%s', DOKU_LF);
              $renderer->doc .= sprintf('      <p>%s', DOKU_LF);
              foreach ($this->a_areas[$this->nr_imagemap_render] as $key => $value) {
                list($state, $match_type, $is_correct, $err_msg, $link, $loc_id, $text, $a_coords) = $value;
                $link = ($link == "") ? "#" : $link;
                $renderer->doc .= sprintf('      <a id="imapmarkers-link-%d-%d" title="%s" href="%s" class="%s" rel="ugc nofollow">%s</a>%s', $this->nr_imagemap_render, $key, $link, $uri, $classes, $text, DOKU_LF);
              }
              $renderer->doc .= sprintf('      </p>%s', DOKU_LF);
              $renderer->doc .= sprintf('    </div>%s', DOKU_LF);
              $renderer->doc .= sprintf('  </map>%s', DOKU_LF);
            }
          }
          if ($nr_cfgs == 1) {
            foreach ($this->a_cfg[$this->nr_imagemap_render] as $value) {
              list($state, $match_type, $is_correct, $err_msg) = $value;
              if (!$is_correct) {
                $renderer->doc .= sprintf('  <br /><span style="color:white; background-color:red;">ERROR -- %s</span>%s', $err_msg, DOKU_LF);
                $is_all_ok = false;
              }
            }
            if ($is_all_ok) {
              foreach ($this->a_cfg[$this->nr_imagemap_render] as $key => $value) {
                list($state, $match_type, $is_correct, $err_msg, $cfg) = $value;
                $renderer->doc .= sprintf('  <div id="imapmarkers-config-%d" class="imapmarkers imapmarkers-config" style="display: none;">%s</div>%s', $this->nr_imagemap_render, $cfg, DOKU_LF);
              }
            }
          }
          break;
      }
    }
    return true;
  } // public function render
} // class syntax_plugin_imapmarkers_map