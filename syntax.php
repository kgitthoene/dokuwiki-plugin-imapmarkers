<?php
/**
 * DokuWiki Plugin imapmarkers (Syntax Component)
 *
 * @license MIT 
 * @author  Kai Thöne <k.git.thoene@gmx.net>
 */
if (!defined('DOKU_INC'))
  die();

#declare(strict_types=1);

use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\Logger;

dbglog("syntax_plugin_imapmarkers::START");


class syntax_plugin_imapmarkers_substitution extends DokuWiki_Syntax_Plugin
{
  public function getType()
  {
    return 'substition';
  }
  public function getSort()
  {
    return 32;
  }

  public function connectTo($mode)
  {
    $this->Lexer->addSpecialPattern('\{{2}(?i)IMAPMLOC>.+?\}{2}', $mode, 'plugin_imapmarkers_substitution');
  }

  public function handle($match, $state, $pos, Doku_Handler $handler)
  {
    global $conf;
    global $ID;
    $args = array($state);
    switch ($state) {
      case DOKU_LEXER_SPECIAL:
        // check for marker location:
        $is_correct = false;
        $err_msg = "";
        $matches = array();
        $match = trim($match);
        if (preg_match("/\{{2}(?i)IMAPMLOC>\s*(.+?)\s*\|\s*(.+?)\s*\}{2}/", $match, $matches)) {
          $loc_id = $matches[1];
          $loc_title = $matches[2];
          $is_correct = true;
          $args = array($state, MATCH_IS_LOCATION, $is_correct, $err_msg, $loc_id, $loc_title);
        } else {
          $err_msg = sprintf("Malformed location! LOCATION='%s'", $match);
          $args = array($state, MATCH_IS_UNKNOWN, $is_correct, $err_msg);
        }
        dbglog(sprintf("syntax_plugin_imapmarkers_substitution.handle::DOKU_LEXER_SPECIAL: [%d] MATCH='%s'", $this->nr_imagemap_handler, $match));
        break;
    }
    return $args;
  }

  public function render($mode, Doku_Renderer $renderer, $data)
  {
    if ($mode == 'xhtml') {
      global $conf;
      global $ID;
      $state = $data[0];
      //dbglog("syntax_plugin_imapmarkers.render: ID='" . cleanID($ID) . "' STATE=" . $state);
      static $has_content = false;
      switch ($state) {
        case DOKU_LEXER_SPECIAL:
          dbglog(sprintf("syntax_plugin_imapmarkers.render::DOKU_LEXER_SPECIAL: [%d] DATA='%s'", $this->nr_imagemap_render, implode($data, ", ")));
          $match_type = MATCH_IS_UNKNOWN;
          $is_correct = false;
          $err_msg = "";
          list($state, $match_type, $is_correct, $err_msg) = $data;
          if ($is_correct) {
            switch ($match_type) {
              case MATCH_IS_LOCATION:
                $is_correct = true;
                list($state, $match_type, $is_correct, $err_msg, $loc_id, $loc_title) = $data;
                $renderer->doc .= sprintf('<span class="imapmarkers imapmarkers-location" location_id="%s">%s</span>', $loc_id, $loc_title);
                break;
            }
          }
          if (!$is_correct) {
            $renderer->doc .= sprintf('  <br /><span style="color:white; background-color:red;">ERROR -- %s</span>%s', $err_msg, DOKU_LF);
          }
          //dbglog(sprintf("DOC='%s'", $renderer->doc));
          break;
      }
      return true;
    }
    //dbglog(sprintf("syntax_plugin_imapmarkers.render| MODE='%s' ID='%s'", $mode, cleanID($ID)));
    return true;
  } // public function render
} // class syntax_plugin_imapmarkers_substitution


class syntax_plugin_imapmarkers extends DokuWiki_Syntax_Plugin
{
  private const MATCH_IS_UNKNOWN = 0;
  private const MATCH_IS_AREA = 1;
  private const MATCH_IS_CONFIG = 2;
  private const MATCH_IS_LOCATION = 3;

  public int $nr_imagemap_handler;
  public int $nr_imagemap_render;
  private array $a_areas;
  private array $a_cfg;

  function __construct()
  {
    global $ID;
    dbglog("syntax_plugin_imapmarkers.__construct ID='" . cleanID($ID) . "'");
    $this->nr_imagemap_handler = -1;
    $this->nr_imagemap_render = -1;
    $this->a_areas = array();
    $this->a_cfg = array();
  }

  /** @inheritDoc */
  public function getType()
  {
    return 'container';
  }

  /** @inheritDoc */
  public function getPType()
  {
    return 'block';
  }

  /** @inheritDoc */
  public function getSort()
  {
    return 200;
  }

  public function getAllowedTypes()
  {
    return array('formatting', 'substition', 'disabled', 'protected', 'container', 'paragraphs');
  }

  /** @inheritDoc */
  public function connectTo($mode)
  {
    if ($mode == "base") {
      global $ID;
      dbglog(sprintf("syntax_plugin_imapmarkers.connectTo: ID='%s' MODE='%s'", cleanID($ID), $mode));
      //$this->Lexer->addSpecialPattern('<FIXME>', $mode, 'plugin_imapmarkers');
      $this->Lexer->addEntryPattern('\{{2}(?i)IMAPMARKERS>[^\}]+\}{2}', $mode, 'plugin_imapmarkers');
      $this->Lexer->addPattern('\s*\{{2}(?i)CFG>\}\}.*?\{\{<CFG\s*\}{2}\s*', 'plugin_imapmarkers');
      $this->Lexer->addPattern('\s*\[{2}.+?\]{2}\s*', 'plugin_imapmarkers');
      $this->Lexer->addSpecialPattern('\{{2}(?i)IMAPMLOC>.+?\}{2}', $mode, 'plugin_imapmarkers_substitution');
    }
  }

  /** @inheritDoc */
  public function postConnect()
  {
    global $ID;
    dbglog("syntax_plugin_imapmarkers.postConnect: ID='" . cleanID($ID) . "'");
    $this->Lexer->addExitPattern('\{\{<(?i)IMAPMARKERS\}\}', 'plugin_imapmarkers');
  }

  /** @inheritDoc */
  public function handle($match, $state, $pos, Doku_Handler $handler)
  {
    global $conf;
    global $ID;
    $args = array($state);
    //dbglog("syntax_plugin_imapmarkers.handle: ID='".cleanID($ID)."' STATE=".$state);

    switch ($state) {
      case DOKU_LEXER_ENTER:
        $this->nr_imagemap_handler++;
        dbglog(sprintf("syntax_plugin_imapmarkers.handle::DOKU_LEXER_ENTER: [%d] MATCH='%s' HANDLER='%s'", $this->nr_imagemap_handler, $match, substr($match, 14, -2)));
        $img = Doku_Handler_Parse_Media(substr($match, 14, -2));
        dbglog(sprintf("syntax_plugin_imapmarkers.handle::DOKU_LEXER_ENTER: [%d] IMG='%s'", $this->nr_imagemap_handler, $img));
        if ($img['title']) {
          $mapname = str_replace(':', '', cleanID($img['title']));
          $mapname = ltrim($mapname, '0123456789._-');
        }
        if (empty($mapname)) {
          if ($img['type'] == 'internalmedia') {
            $src = $img['src'];
            $exists = null;
            resolve_mediaid(getNS($ID), $src, $exists);
            $nssep = ($conf['useslash']) ? '[:;/]' : '[:;]';
            $mapname = preg_replace('!.*' . $nssep . '!', '', $src);
          } else {
            $src = parse_url($img['src']);
            $mapname = str_replace(':', '', cleanID($src['host'] . $src['path'] . $src['query']));
            $mapname = ltrim($mapname, '0123456789._-');
          }
          if (empty($mapname)) {
            $mapname = 'imapmarkers' . $pos;
          }
        }
        $args = array(
          $state, $img['type'], $img['src'], $img['title'],
          $mapname,
          $img['align'], $img['width'], $img['height'],
          $img['cache']
        );
        dbglog("syntax_plugin_imapmarkers.handle::DOKU_LEXER_ENTER: ARGS=[ " . implode(", ", $args) . " ]");
        break;
      case DOKU_LEXER_EXIT:
        dbglog("syntax_plugin_imapmarkers.handle::DOKU_LEXER_EXIT: MATCH='" . trim($match) . "'");
        break;
      case DOKU_LEXER_MATCHED:
        $is_correct = false;
        $err_msg = "";
        $matches = array();
        $match = trim($match);
        dbglog(sprintf("syntax_plugin_imapmarkers.handle::DOKU_LEXER_MATCHED: [%d] MATCH='%s' POS=%s", $this->nr_imagemap_handler, $match, $pos));
        //----------
        // check for area:
        if (preg_match("/\[{2}\s*(.*?)\s*\|\s*(.*?)\s*\|\s*(.*?)\s*@\s*([\d,\s]+)\s*\]{2}/", $match, $matches)) {
          if (count($matches) == 5) {
            $link = $matches[1];
            $loc_id = $matches[2];
            $text = $matches[3];
            $coordinates = $matches[4];
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
            $args = array($state, MATCH_IS_AREA, $is_correct, $err_msg, $link, $loc_id, $text, $a_coords);
            break;
          } else {
            $err_msg = sprintf("Invalid area! AREA='%s'", $match);
          }
        } else {
          if (preg_match("/^\{\{(?i)CFG>\}\}\s*(.*?)\s*\{\{<CFG\s*\}\}$/s", $match, $matches)) {
            if (count($matches) == 2) {
              $cfg = $matches[1];
              if (json_decode($cfg)) {
                $is_correct = true;
                $args = array($state, MATCH_IS_CONFIG, $is_correct, $err_msg, $cfg);
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
        $args = array($state, MATCH_IS_UNKNOWN, $is_correct, $err_msg);
        break;
      /*
      case DOKU_LEXER_SPECIAL:
      // check for marker location:
      $is_correct = false;
      $err_msg = "";
      $matches = array();
      $match = trim($match);
      if (preg_match("/\{{2}(?i)IMAPMLOC>\s*(.+?)\s*\|\s*(.+?)\s*\}{2}/", $match, $matches)) {
      $loc_id = $matches[1];
      $loc_title = $matches[2];
      $is_correct = true;
      $args = array($state, MATCH_IS_LOCATION, $is_correct, $err_msg, $loc_id, $loc_title);
      } else {
      $err_msg = sprintf("Malformed location! LOCATION='%s'", $match);
      $args = array($state, MATCH_IS_UNKNOWN, $is_correct, $err_msg);
      }
      dbglog(sprintf("syntax_plugin_imapmarkers.handle::DOKU_LEXER_SPECIAL: [%d] MATCH='%s'", $this->nr_imagemap_handler, $match));
      break;
      */
      case DOKU_LEXER_UNMATCHED:
        $args[] = $match;
        dbglog("syntax_plugin_imapmarkers.handle::DOKU_LEXER_UNMATCHED: DATA='" . trim($match) . "'");
        break;
    }
    return $args;
  } // public function handle

  /** @inheritDoc */
  public function render($mode, Doku_Renderer $renderer, $data)
  {
    if ($mode == 'xhtml') {
      global $conf;
      global $ID;
      $state = $data[0];
      //dbglog("syntax_plugin_imapmarkers.render: ID='" . cleanID($ID) . "' STATE=" . $state);
      static $has_content = false;
      switch ($state) {
        case DOKU_LEXER_ENTER:
          $this->nr_imagemap_render++;
          dbglog(sprintf("syntax_plugin_imapmarkers.render::DOKU_LEXER_ENTER: [%d] DATA='%s'", $this->nr_imagemap_render, implode($data, ", ")));
          list($state, $type, $src, $title, $name, $align, $width, $height, $cache) = $data;
          if ($type == 'internalmedia') {
            $exists = null;
            resolve_mediaid(getNS($ID), $src, $exists);
          }
          $renderer->doc .= sprintf('<p id="imapmarkers-container-%d" class="imapmarkers imapmarkers-container">%s', $this->nr_imagemap_render, DOKU_LF);
          //dbglog(sprintf("DOC='%s'", $renderer->doc));
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
          $match_type = MATCH_IS_UNKNOWN;
          $is_correct = false;
          $err_msg = "";
          list($state, $match_type, $is_correct, $err_msg) = $data;
          switch ($match_type) {
            case MATCH_IS_AREA:
              if (!array_key_exists($this->nr_imagemap_render, $this->a_areas)) {
                $this->a_areas[$this->nr_imagemap_render] = array();
              }
              array_push($this->a_areas[$this->nr_imagemap_render], $data);
              break;
            case MATCH_IS_CONFIG:
              if (!array_key_exists($this->nr_imagemap_render, $this->a_cfg)) {
                $this->a_cfg[$this->nr_imagemap_render] = array();
              }
              array_push($this->a_cfg[$this->nr_imagemap_render], $data);
              break;
          }
          dbglog(sprintf("syntax_plugin_imapmarkers.render::DOKU_LEXER_MATCHED: [%d] DATA='%s'", $this->nr_imagemap_render, implode($data, ", ")));
          if (!$is_correct) {
            $renderer->doc .= sprintf('  <br /><span style="color:white; background-color:red;">ERROR -- %s</span>%s', $err_msg, DOKU_LF);
          }
          break;
        case DOKU_LEXER_UNMATCHED:
          dbglog(sprintf("syntax_plugin_imapmarkers.render::DOKU_LEXER_UNMATCHED: [%d] DATA='%s'", $this->nr_imagemap_render, implode($data, ", ")));
          break;
        case DOKU_LEXER_EXIT:
          $is_all_ok = true;
          $nr_areas = 0;
          $nr_cfgs = 0;
          if (array_key_exists($this->nr_imagemap_render, $this->a_areas)) {
            $nr_areas = count($this->a_areas[$this->nr_imagemap_render]);
          }
          if (array_key_exists($this->nr_imagemap_render, $this->a_cfg)) {
            $nr_cfgs = 1;
          }
          dbglog(sprintf("syntax_plugin_imapmarkers.render::DOKU_LEXER_EXIT: [%d] DATA='%s' #AREAS=%d #CFGS=%d", $this->nr_imagemap_render, implode($data, ", "), $nr_areas, $nr_cfgs));
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
                list($state, $match_type, $is_correct, $err_msg, $link, $loc_id, $text, $a_coords) = $value;
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
                //dbglog(sprintf("syntax_plugin_imapmarkers.render::DOKU_LEXER_EXIT: [%d] COORDS='%s'", $this->nr_imagemap_render, implode($a_coords, ",")));
                $renderer->doc .= sprintf('    <area id="imapmarkers-area-%d-%d" location_id="%s" class="imapmarkers" shape="%s" coords="%s" alt="%s" title="%s" href="%s" />%s', $this->nr_imagemap_render, $key, $loc_id, $shape, implode($a_coords, ","), $text, $text, $link, DOKU_LF);
              }
              $renderer->doc .= sprintf('    <div style="display:none;" class="imapcontent">%s', DOKU_LF);
              $renderer->doc .= sprintf('      <p>%s', DOKU_LF);
              foreach ($this->a_areas[$this->nr_imagemap_render] as $key => $value) {
                list($state, $match_type, $is_correct, $err_msg, $link, $loc_id, $text, $a_coords) = $value;
                $link = ($link == "") ? "#" : $link;
                $renderer->doc .= sprintf('      <a id="imapmarkers-link-%d-%d" title="%s" href="%s" rel="ugc nofollow">%s</a>%s', $this->nr_imagemap_render, $key, $link, $link, $text, DOKU_LF);
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
                //dbglog(sprintf("syntax_plugin_imapmarkers.render::DOKU_LEXER_EXIT: [%d] CONFIG='%s'", $cfg));
                $renderer->doc .= sprintf('  <div id="imapmarkers-config-%d" class="imapmarkers imapmarkers-config" style="display: none;">%s</div>%s', $this->nr_imagemap_render, $cfg, DOKU_LF);
              }
            }
          }
          break;
      }
      return true;
    }
    //dbglog(sprintf("syntax_plugin_imapmarkers.render| MODE='%s' ID='%s'", $mode, cleanID($ID)));
    return true;
  } // public function render
} // class syntax_plugin_imapmarkers