<?php
/**
 * DokuWiki Plugin imapmarkers (Syntax Component)
 *
 * @license MIT 
 * @author  Kai Thöne <k.git.thoene@gmx.net>
 */
if (!defined('DOKU_INC'))
  die();

//if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
//if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
//require_once(DOKU_PLUGIN.'syntax.php');


class syntax_plugin_imapmarkers_syntax extends \dokuwiki\Extension\SyntaxPlugin
{
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
    return 316;
  }

  /** @inheritDoc */
  public function connectTo($mode)
  {
    //$this->Lexer->addSpecialPattern('<FIXME>', $mode, 'plugin_imapmarkers_syntax');
    $this->Lexer->addEntryPattern('\{\{imapmarkers>[^\}]+\}\}', $mode, 'plugin_imapmarkers_syntax');
  }

  /** @inheritDoc */
  public function postConnect()
  {
    $this->Lexer->addExitPattern('\{\{<imapmarkers\}\}', 'plugin_imapmarkers_syntax');
  }

  /** @inheritDoc */
  public function handle($match, $state, $pos, Doku_Handler $handler)
  {
    global $conf;
    global $ID;
    $args = array($state);

    switch ($state) {
      case DOKU_LEXER_ENTER:
        $img = Doku_Handler_Parse_Media(substr($match, 6, -2));
        if ($img['title']) {
          $mapname = str_replace(':', '', cleanID($img['title']));
          $mapname = ltrim($mapname, '0123456789._-');
        }
        if (empty($mapname)) {
          if ($img['type'] == 'internalmedia') {
            $src = $img['src'];
            resolve_mediaid(getNS($ID), $src, $exists);
            $nssep = ($conf['useslash']) ? '[:;/]' : '[:;]';
            $mapname = preg_replace('!.*' . $nssep . '!', '', $src);
          } else {
            $src = parse_url($img['src']);
            $mapname = str_replace(':', '', cleanID($src['host'] . $src['path'] . $src['query']));
            $mapname = ltrim($mapname, '0123456789._-');
          }
          if (empty($mapname)) {
            $mapname = 'imagemap' . $pos;
          }
        }
        $args = array(
          $state, $img['type'], $img['src'], $img['title'],
          $mapname,
          $img['align'], $img['width'], $img['height'],
          $img['cache']
        );

        if ($handler->CallWriter) {
          $ReWriter = new ImageMap_Handler($mapname, $handler->CallWriter);
          $handler->CallWriter =& $ReWriter;
        } else {
          $ReWriter = new ImageMap_Handler($mapname, $handler->getCallWriter());
          $handler->setCallWriter($ReWriter);
        }
        break;
      case DOKU_LEXER_EXIT:
        if ($handler->CallWriter) {
          $handler->CallWriter->process();
          $ReWriter = $handler->CallWriter;
          $handler->CallWriter =& $ReWriter->CallWriter;
        } else {
          $handler->getCallWriter()->process();
          $ReWriter = $handler->getCallWriter();
          $handler->setCallWriter($ReWriter->CallWriter);
        }
        break;
      case DOKU_LEXER_MATCHED:
        break;
      case DOKU_LEXER_UNMATCHED:
        $args[] = $match;
        break;
    }
    return $args;
  }

  /** @inheritDoc */
  public function render($mode, Doku_Renderer $renderer, $data)
  {
    if ($mode !== 'xhtml') {
      global $conf;
      global $ID;
      static $has_content = false;
      $state = $data[0];
      switch ($state) {
        case DOKU_LEXER_ENTER:
          list($state, $type, $src, $title, $name, $align, $width, $height, $cache) = $data;
          return true;
          break;
      }
      return false;
    }

    return true;
  }
}