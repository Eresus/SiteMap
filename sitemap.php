<?php
/**
 * Карта сайта
 *
 * Eresus 2.10
 *
 * Карта разделов сайта
 *
 * @version 3.02
 *
 * @copyright 2006, ProCreat Systems, http://procreat.ru/
 * @copyright 2007, Eresus Group, http://eresus.ru/
 * @copyright 2010, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author Mikhail Krasilnikov <mk@procreat.ru>
 * @author Ghost <ghost@dvaslona.ru>
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
 *
 * @package Sitemap
 *
 * $Id: sitemap.php 60 2010-03-01 03:41:02Z ghost $
 */

/**
 * Класс плагина
 *
 * @package Sitemap
 */
class SiteMap extends ContentPlugin
{
	var $version = '3.02a';
	var $kernel = '2.10';
	var $title = 'Карта сайта';
	var $description = 'Карта разделов сайта';
	var $type = 'client,content,ondemand';
	var $settings = array (
		'tmplList' => '<ul class="level$(level)">$(items)</ul>',
		'tmplItem' => '<li><a href="$(url)" title="$(hint)">$(caption)</a>$(subitems)</li>',
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
		global $page;

		$form = array(
			'name'=>'SettingsForm',
			'caption' => $this->title.' '.$this->version,
			'width' => '500px',
			'fields' => array(
				array('type'=>'hidden','name'=>'update', 'value'=>$this->name),
				array('type'=>'header', 'value'=>'Шаблоны'),
				array('type'=>'memo','name'=>'tmplList','label'=>'Шаблон блока одного уровня меню',
					'height' => '3'),
				array('type'=>'text',
					'value' => 'Макросы:<ul><li><b>$(level)</b> - номер текущего уровня</li><li><b>$(items)' .
					'</b> - подразделы</li></ul>'),
				array('type'=>'memo','name'=>'tmplItem','label'=>'Шаблон пункта меню', 'height' => '3'),
				array('type'=>'text', 'value' => 'Макросы:<ul><li><b>Все элементы страницы</b></li><li>' .
					'<b>$(level)</b> - номер текущего уровня</li><li><b>$(url)</b> - ссылка</li><li><b>' .
					'$(subitems)</b> - место для вставки подразделов</li></ul>'),
				array('type'=>'header', 'value'=>'Опции'),
				array('type'=>'checkbox','name'=>'showHidden','label'=>'Показывать невидимые'),
				array('type'=>'checkbox','name'=>'showPriveleged',
					'label'=>'Показывать независимо от уровня доступа'),
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
	 * @param int    $level  уровень вложенности
	 * @return string
	 */
	function branch($owner = 0, $level = 0)
	{
		global $Eresus, $page;

		$result = '';

		$flags = SECTIONS_ACTIVE;
		if (!$this->settings['showHidden'])
		{
			$flags += SECTIONS_VISIBLE;
		}

		$access = $this->settings['showPriveleged'] ? ROOT : $Eresus->user['access'];

		$items = $Eresus->sections->children($owner, $access, $flags);

		if (count($items))
		{
			foreach ($items as $item)
			{
				$item = $Eresus->sections->get($item['id']);
				if ($item['type'] == 'url')
				{
					$item['url'] = $item['content'];
				}
				else
				{
					$item['url'] = $page->clientURL($item['id']);
				}
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

		if ($extra_GET_arguments)
		{
			$page->httpError(404);
		}
		if ($is_ARG_request)
		{
			$page->httpError(404);
		}

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
				'<p>Содержимое этого раздела создаётся автоматически на основе <a href="'.$Eresus->root.
				'admin.php?mod=pages">структуры разделов</a> сайта.</p>'.
				'<p>Настроить внешний вид можно в <a href="'.$Eresus->root.'admin.php?mod=plgmgr&id='.
				$this->name.'">настройках модуля</a>.</p>',
		);

		$result = $page->window($wnd);

		return $result;
	}
	//-----------------------------------------------------------------------------
}