<?php
/**
 * ����� �����
 *
 * Eresus 2.10
 *
 * ����� �������� �����
 *
 * @version 3.01
 *
 * @copyright 2006, ProCreat Systems, http://procreat.ru/
 * @copyright 2007, Eresus Group, http://eresus.ru/
 * @copyright 2010, ��� "��� �����", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author Mikhail Krasilnikov <mk@procreat.ru>
 * @author Ghost <ghost@dvaslona.ru>
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
 *
 * @package Sitemap
 *
 * $Id: sitemap.php 60 2010-03-01 03:41:02Z ghost $
 */

/**
 * ����� �������
 *
 * @package Sitemap
 */
class SiteMap extends ContentPlugin
{
	var $version = '3.01a';
	var $kernel = '2.10';
	var $title = '����� �����';
	var $description = '����� �������� �����';
	var $type = 'client,content,ondemand';
	var $settings = array (
		'tmplList' => '<ul class="level$(level)">$(items)</ul>',
		'tmplItem' => '<li><a href="$(url)" title="$(hint)">$(caption)</a>$(subitems)</li>',
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
		global $page;

		$form = array(
			'name'=>'SettingsForm',
			'caption' => $this->title.' '.$this->version,
			'width' => '500px',
			'fields' => array (
				array('type'=>'hidden','name'=>'update', 'value'=>$this->name),
				array('type'=>'header', 'value'=>'�������'),
				array('type'=>'memo','name'=>'tmplList','label'=>'������ ����� ������ ������ ����', 'height' => '3'),
				array('type'=>'text', 'value' => '�������:<ul><li><b>$(level)</b> - ����� �������� ������</li><li><b>$(items)</b> - ����������</li></ul>'),
				array('type'=>'memo','name'=>'tmplItem','label'=>'������ ������ ����', 'height' => '3'),
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
	 * @param int    $level  ������� �����������
	 * @return string
	 */
	function branch($owner = 0, $level = 0)
	{
		global $Eresus, $page;

		$result = '';

		$flags = SECTIONS_ACTIVE;
		if (!$this->settings['showHidden']) $flags += SECTIONS_VISIBLE;

		$access = $this->settings['showPriveleged'] ? ROOT : $Eresus->user['access'];

		$items = $Eresus->sections->children($owner, $access, $flags);

		if (count($items)) {
			foreach($items as $item) {
				$item = $Eresus->sections->get($item['id']);
				if ($item['type'] == 'url') $item['url'] = $item['content'];
				else $item['url'] = $page->clientURL($item['id']);
				$item['level'] = $level+1;
				$item['subitems'] = $this->branch($item['id'], $level+1);
				$result .= $this->replaceMacros($this->settings['tmplItem'], $item);
			}
			$result = array('level'=>($level+1), 'items'=>$result);
			$result = $this->replaceMacros($this->settings['tmplList'], $result);
		}
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ???
	 * @return string
	 */
	function clientRenderContent()
	{
		global $Eresus, $page;

		$extra_GET_arguments = $Eresus->request['url'] != $Eresus->request['path'];
		$is_ARG_request = count($Eresus->request['arg']);

		if ($extra_GET_arguments) $page->httpError(404);
		if ($is_ARG_request) $page->httpError(404);

		$result = $this->branch();
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ???
	 * @return string
	 */
	function adminRenderContent()
	{
		global $Eresus, $page;

		$wnd = array(
			'caption' => $this->title,
			'body' =>
				'<p>���������� ����� ������� �������� ������������� �� ������ <a href="'.$Eresus->root.'admin.php?mod=pages">��������� ��������</a> �����.</p>'.
				'<p>��������� ������� ��� ����� � <a href="'.$Eresus->root.'admin.php?mod=plgmgr&id='.$this->name.'">���������� ������</a>.</p>',
		);

		$result = $page->window($wnd);

		return $result;
	}
	//-----------------------------------------------------------------------------
}