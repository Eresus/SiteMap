<?php
/**
  * SiteMap
  *
  * Eresus 2
  *
  * ����� �����
  *
  * @version 2.01
  *
  * @copyright   2006, ProCreat Systems, http://procreat.ru/
  * @copyright   2007, Eresus Group, http://eresus.ru/
  * @license     http://www.gnu.org/licenses/gpl.txt  GPL License 3
  * @maintainer  Mikhail Krasilnikov <mk@procreat.ru>
  * @author      Mikhail Krasilnikov <mk@procreat.ru>
  *
  * ������ ��������� �������� ��������� ����������� ������������. ��
  * ������ �������������� �� �/��� �������������� � ������������ �
  * ��������� ������ 3 ���� (�� ������ ������) � ��������� ����� �������
  * ������ ����������� ������������ �������� GNU, �������������� Free
  * Software Foundation.
  *
  * �� �������������� ��� ��������� � ������� �� ��, ��� ��� ����� ���
  * ��������, ������ �� ������������� �� ��� ������� ��������, � ���
  * ����� �������� ��������� ��������� ��� ������� � ����������� ���
  * ������������� � ���������� �����. ��� ��������� ����� ���������
  * ���������� ������������ �� ����������� ������������ ��������� GNU.
  *
  * �� ������ ���� �������� ����� ����������� ������������ ��������
  * GNU � ���� ����������. ���� �� �� �� ��������, �������� �������� ��
  * <http://www.gnu.org/licenses/>
  */


class TSiteMap extends TContentPlugin {
  var $name = 'sitemap';
  var $type = 'client,content,ondemand';
  var $title = '����� �����';
  var $version = '2.01';
  var $description = '����� �������� �����';
  var $settings = array (
    'tmplList' => '<table class="level$(level)">$(items)</table>',
    'tmplItem' => '<tr><td><a href="$(url)" title="$(hint)">$(caption)</a>$(subitems)</td></tr>',
    'showHidden' => false,
    'showPriveleged' => false,
  );
  //-----------------------------------------------------------------------------
  /**
   * ��������� �������
   *
   * @return string  ������ ��������
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
        array('type'=>'header', 'value'=>'�������'),
        array('type'=>'memo','name'=>'tmplList','label'=>'������ ����� ������ ������ ����', 'height' => '3'),
        array('type'=>'text', 'value' => '�������:<ul><li><b>$(level)</b> - ����� �������� ������</li><li><b>$(items)</b> - ����������</li></ul>'),
        array('type'=>'memo','name'=>'tmplItem','label'=>'������ ����', 'height' => '3'),
        array('type'=>'text', 'value' => '�������:<ul><li><b>��� �������� ��������</b></li><li><b>$(level)</b> - ����� �������� ������</li><li><b>$(url)</b> - ������</li><li><b>$(subitems)</b> - ����� ��� ������� �����������</li></ul>'),
        array('type'=>'header', 'value'=>'�����'),
        array('type'=>'checkbox','name'=>'showHidden','label'=>'���������� ���������'),
        array('type'=>'checkbox','name'=>'showPriveleged','label'=>'���������� ���������� �� ������ �������'),
      ),
      'buttons' => array('ok', 'apply', 'cancel'),
    );
    $result = $page->renderForm($form, $this->settings);
    return $result;
  }
  //-----------------------------------------------------------------------------
 /**
  * ���������� �����
  *
  * @param int    $owner  ID ��������� ������
  * @param string $path   ����������� ���� � ���������
  * @param int    $level  ������� �����������
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
    #TODO: ��������!!!

    return $result = '*';
  }
  //-----------------------------------------------------------------------------
}
?>