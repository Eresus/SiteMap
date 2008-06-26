<?php
/**
  * SiteMap
  *
  * Eresus 2
  *
  * Карта сайта
  *
  * @version 2.01
  *
  * @copyright   2006, ProCreat Systems, http://procreat.ru/
  * @copyright   2007, Eresus Group, http://eresus.ru/
  * @license     http://www.gnu.org/licenses/gpl.txt  GPL License 3
  * @maintainer  Mikhail Krasilnikov <mk@procreat.ru>
  * @author      Mikhail Krasilnikov <mk@procreat.ru>
  *
  * Данная программа является свободным программным обеспечением. Вы
  * вправе распространять ее и/или модифицировать в соответствии с
  * условиями версии 3 либо (по вашему выбору) с условиями более поздней
  * версии Стандартной Общественной Лицензии GNU, опубликованной Free
  * Software Foundation.
  *
  * Мы распространяем эту программу в надежде на то, что она будет вам
  * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
  * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
  * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
  * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
  *
  * Вы должны были получить копию Стандартной Общественной Лицензии
  * GNU с этой программой. Если Вы ее не получили, смотрите документ на
  * <http://www.gnu.org/licenses/>
  */


class TSiteMap extends TContentPlugin {
  var $name = 'sitemap';
  var $type = 'client,content,ondemand';
  var $title = 'Карта сайта';
  var $version = '2.01';
  var $description = 'Карта разделов сайта';
  var $settings = array (
    'tmplList' => '<table class="level$(level)">$(items)</table>',
    'tmplItem' => '<tr><td><a href="$(url)" title="$(hint)">$(caption)</a>$(subitems)</td></tr>',
    'showHidden' => false,
    'showPriveleged' => false,
  );
  //-----------------------------------------------------------------------------
  /**
   * Настройки плагина
   *
   * @return string  Диалог настроек
   */
  function settings()
  {
  global $page, $db;

    $form = array(
      'name'=>'SettingsForm',
      'caption' => $this->title.' '.$this->version,
      'width' => '500px',
      'fields' => array (
        array('type'=>'hidden','name'=>'update', 'value'=>$this->name),
        array('type'=>'header', 'value'=>'Шаблоны'),
        array('type'=>'memo','name'=>'tmplList','label'=>'Шаблон блока одного уровня меню', 'height' => '3'),
        array('type'=>'text', 'value' => 'Макросы:<ul><li><b>$(level)</b> - номер текущего уровня</li><li><b>$(items)</b> - подразделы</li></ul>'),
        array('type'=>'memo','name'=>'tmplItem','label'=>'Пункта меню', 'height' => '3'),
        array('type'=>'text', 'value' => 'Макросы:<ul><li><b>Все элементы страницы</b></li><li><b>$(level)</b> - номер текущего уровня</li><li><b>$(url)</b> - ссылка</li><li><b>$(subitems)</b> - место для вставки подразделов</li></ul>'),
        array('type'=>'header', 'value'=>'Опции'),
        array('type'=>'checkbox','name'=>'showHidden','label'=>'Показывать невидимые'),
        array('type'=>'checkbox','name'=>'showPriveleged','label'=>'Показывать независимо от уровня доступа'),
      ),
      'buttons' => array('ok', 'apply', 'cancel'),
    );
    $result = $page->renderForm($form, $this->settings);
    return $result;
  }
  //-----------------------------------------------------------------------------
 /**
  * Построение ветки
  *
  * @param int    $owner  ID корневого предка
  * @param string $path   Виртуальный путь к страницам
  * @param int    $level  уровень вложенности
  * @return string
  */
  function branch($owner = 0, $path = '', $level = 0)
  #
  {
    global $Eresus, $db, $user, $page;

    $result = '';
    if (strpos($path, httpRoot) !== false) $path = substr($path, strlen(httpRoot));
    $items = $db->select('`pages`', "(`owner`='".$owner."') AND (`active`='1')".($this->settings['showPriveleged']?'':" AND (`access`>='".($user['auth']?$user['access']:GUEST)."')").($this->settings['showHidden']?'':" AND (`visible` = '1')"), "`position`");
    if (count($items)) {
      foreach($items as $item) {
        if ($item['type'] == 'url') {
          $item['options'] = decodeOptions($item['options']);
          $item['url'] = $item['content'];
        } else $item['url'] = httpRoot.$path.($item['name']=='main'?'':$item['name'].'/');
        $item['level'] = $level+1;
        $item['selected'] = $item['id'] == $page->id;
        $item['subitems'] = $this->branch($item['id'], $path.$item['name'].'/', $level+1);
        $result .= $this->replaceMacros($this->settings['tmplItem'], $item);
      }
      $result = array('level'=>($level+1), 'items'=>$result);
      $result = $this->replaceMacros($this->settings['tmplList'], $result);
    }
    return $result;
  }
  //-----------------------------------------------------------------------------
  function clientRenderContent()
  {
  	global $Eresus;

  	$items = $Eresus->sections->branch(0);
    #TODO: Доделать!!!

    return $result = '*';
  }
  //-----------------------------------------------------------------------------
}
?>