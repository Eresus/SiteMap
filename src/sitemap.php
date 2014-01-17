<?php
/**
 * Карта сайта
 *
 * Карта разделов сайта
 *
 * @version ${product.version}
 *
 * @copyright 2006, Михаил Красильников <mihalych@vsepofigu.ru>
 * @copyright 2007, Eresus Group, http://eresus.ru/
 * @copyright 2010, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author Михаил Красильников <mihalych@vsepofigu.ru>
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
 */

/**
 * Класс плагина
 *
 * @package Sitemap
 */
class SiteMap extends ContentPlugin
{
    /**
     * Версия
     * @var string
     */
    public $version = '${product.version}';

    /**
     * Требуемая версия CMS
     * @var string
     */
    public $kernel = '3.00b';

    /**
     * Название
     * @var string
     */
    public $title = 'Карта сайта';

    /**
     * Описание
     * @var string
     */
    public $description = 'Карта разделов сайта';

    /**
     * Версия
     * @var string
     */
    public $settings = array(
        'tmplList' => '<ul class="level$(level)">$(items)</ul>',
        'tmplItem' => '<li><a href="$(url)" title="$(hint)">$(caption)</a>$(subitems)</li>',
        'showHidden' => false,
        'showPriveleged' => false,
    );

    /**
     * Настройки плагина
     *
     * @return string  Диалог настроек
     */
    public function settings()
    {
        $form = array(
            'name' => 'SettingsForm',
            'caption' => $this->title . ' ' . $this->version,
            'width' => '500px',
            'fields' => array(
                array('type' => 'hidden', 'name' => 'update', 'value' => $this->name),
                array('type' => 'header', 'value' => 'Шаблоны'),
                array('type' => 'memo', 'name' => 'tmplList', 'label' => 'Шаблон блока одного уровня меню',
                    'height' => '3'),
                array('type' => 'text',
                    'value' => 'Макросы:<ul><li><b>$(level)</b> - номер текущего уровня</li><li><b>$(items)' .
                        '</b> - подразделы</li></ul>'),
                array('type' => 'memo', 'name' => 'tmplItem', 'label' => 'Шаблон пункта меню', 'height' => '3'),
                array('type' => 'text', 'value' => 'Макросы:<ul><li><b>Все элементы страницы</b></li><li>' .
                    '<b>$(level)</b> - номер текущего уровня</li><li><b>$(url)</b> - ссылка</li><li><b>' .
                    '$(subitems)</b> - место для вставки подразделов</li></ul>'),
                array('type' => 'header', 'value' => 'Опции'),
                array('type' => 'checkbox', 'name' => 'showHidden', 'label' => 'Показывать невидимые'),
                array('type' => 'checkbox', 'name' => 'showPriveleged',
                    'label' => 'Показывать независимо от уровня доступа'),
            ),
            'buttons' => array('ok', 'apply', 'cancel'),
        );
        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $result = $page->renderForm($form, $this->settings);
        return $result;
    }

    /**
     * Построение ветки
     *
     * @param int $owner ID корневого предка
     * @param int $level уровень вложенности
     * @return string
     */
    private function branch($owner = 0, $level = 0)
    {
        $result = '';

        $flags = SECTIONS_ACTIVE;
        if (!$this->settings['showHidden'])
        {
            $flags += SECTIONS_VISIBLE;
        }

        $access = $this->settings['showPriveleged'] ? ROOT :
            Eresus_CMS::getLegacyKernel()->user['access'];

        $items = Eresus_CMS::getLegacyKernel()->sections->children($owner, $access, $flags);

        if (count($items))
        {
            foreach ($items as $item)
            {
                $item = Eresus_CMS::getLegacyKernel()->sections->get($item['id']);
                if ($item['type'] == 'url')
                {
                    $item['url'] = $item['content'];
                }
                else
                {
                    $item['url'] = Eresus_Kernel::app()->getPage()->clientURL($item['id']);
                }
                $item['level'] = $level + 1;
                $item['subitems'] = $this->branch($item['id'], $level + 1);
                $result .= $this->replaceMacros($this->settings['tmplItem'], $item);
            }
            $result = array('level' => ($level + 1), 'items' => $result);
            $result = $this->replaceMacros($this->settings['tmplList'], $result);
        }
        return $result;
    }

    /**
     * ???
     * @return string
     */
    public function clientRenderContent()
    {
        $extra_GET_arguments = Eresus_CMS::getLegacyKernel()->request['url'] !=
            Eresus_CMS::getLegacyKernel()->request['path'];
        $is_ARG_request = count(Eresus_CMS::getLegacyKernel()->request['arg']);

        /** @var TClientUI $page */
        $page = Eresus_Kernel::app()->getPage();
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

    /**
     * ???
     * @return string
     */
    public function adminRenderContent()
    {
        $wnd = array(
            'caption' => $this->title,
            'body' =>
                '<p>Содержимое этого раздела создаётся автоматически на основе <a href="' .
                Eresus_CMS::getLegacyKernel()->root .
                'admin.php?mod=pages">структуры разделов</a> сайта.</p>' .
                '<p>Настроить внешний вид можно в <a href="' .
                Eresus_CMS::getLegacyKernel()->root . 'admin.php?mod=plgmgr&id=' .
                $this->name . '">настройках модуля</a>.</p>',
        );

        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $result = $page->window($wnd);

        return $result;
    }
}

