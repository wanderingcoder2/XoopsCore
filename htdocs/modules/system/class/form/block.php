<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * Blocks Form Class
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         GNU GPL 2 (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @author          Andricq Nicolas (AKA MusS)
 * @package         system
 * @subpackage      blocksadmin
 * @version         $Id$
 */

defined('XOOPS_ROOT_PATH') or die('Restricted access');

class SystemBlockForm extends XoopsThemeForm
{
    /**
     * @var null|XoopsObject $_obj
     */
    private $_obj = null;

    /**
     * @param XoopsBlock|XoopsObject $obj
     */
    public function __construct(XoopsBlock &$obj)
    {
        $this->_obj = $obj;
    }

    /**
     * @param string $mode
     *
     * @return void
     */
    public function getForm($mode = 'edit')
    {
        $xoops = Xoops::getInstance();
        $xoops->loadLanguage('blocks', 'system');
        if ($this->_obj->isNew()) {
            $title = SystemLocale::ADD_BLOCK;
            $modules = array(-1);
            $groups = array(XOOPS_GROUP_USERS, XOOPS_GROUP_ANONYMOUS, XOOPS_GROUP_ADMIN);
            $this->_obj->setVar('block_type', 'C');
            $this->_obj->setVar('visible', 1);
            $op = 'save';
        } else {
            $title = '';
            $modules = array();
            // Search modules
            $blockmodulelink_handler = $xoops->getHandlerBlockmodulelink();
            $criteria = new CriteriaCompo(new Criteria('block_id', $this->_obj->getVar('bid')));
            $blockmodulelink = $blockmodulelink_handler->getObjects($criteria);
            /* @var $link XoopsBlockmodulelink */
            foreach ($blockmodulelink as $link) {
                $modules[] = $link->getVar('module_id');
            }
            // Search perms
            $groupperm_handler = $xoops->getHandlerGroupperm();
            $groups = $groupperm_handler->getGroupIds('block_read', $this->_obj->getVar('bid'));
            switch ($mode) {
                case 'edit':
                    $title = SystemLocale::EDIT_BLOCK;
                    break;
                case 'clone':
                    $title = SystemLocale::CLONE_BLOCK;
                    $this->_obj->setVar('bid', 0);
                    if ($this->_obj->isCustom()) {
                        $this->_obj->setVar('block_type', 'C');
                    } else {
                        $this->_obj->setVar('block_type', 'D');
                    }
                    break;
            }
            $op = 'save';
        }
        parent::__construct($title, 'blockform', 'admin.php', 'post', true);
        if (!$this->_obj->isNew()) {
            $this->addElement(new XoopsFormLabel(XoopsLocale::NAME, $this->_obj->getVar('name')));
        }
        // Side position
        $side_select = new XoopsFormSelect(XoopsLocale::SIDE, 'side', $this->_obj->getVar('side'));
        $side_select->addOptionArray(array(
            0 => XoopsLocale::LEFT,
            1 => XoopsLocale::RIGHT,
            3 => SystemLocale::TOP_LEFT,
            4 => SystemLocale::TOP_RIGHT,
            5 => SystemLocale::TOP_CENTER,
            7 => SystemLocale::BOTTOM_LEFT,
            8 => SystemLocale::BOTTOM_RIGHT,
            9 => SystemLocale::BOTTOM_CENTER
        ));
        $this->addElement($side_select);
        // Order
        $weight = new XoopsFormText(XoopsLocale::WEIGHT, 'weight', 1, 5, $this->_obj->getVar('weight'), '');
        $weight->setPattern('^\d+$', XoopsLocale::E_YOU_NEED_A_POSITIVE_INTEGER);
        $this->addElement($weight, true);
        // Display
        $this->addElement(new XoopsFormRadioYN(XoopsLocale::VISIBLE, 'visible', $this->_obj->getVar('visible')));
        // Visible In
        $mod_select = new XoopsFormSelect(XoopsLocale::VISIBLE_IN, 'modules', $modules, 5, true);
        $criteria = new CriteriaCompo(new Criteria('hasmain', 1));
        $criteria->add(new Criteria('isactive', 1));
        $module_list = $xoops->getHandlerModule()->getNameList($criteria);
        $module_list[-1] = XoopsLocale::TOP_PAGE;
        $module_list[0] = XoopsLocale::ALL_PAGES;
        ksort($module_list);
        $mod_select->addOptionArray($module_list);
        $this->addElement($mod_select);
        // Title
        $this->addElement(new XoopsFormText(XoopsLocale::TITLE, 'title', 5, 255, $this->_obj->getVar('title')), false);
        if ($this->_obj->isNew() || $this->_obj->isCustom()) {
            $editor_configs = array();
            $editor_configs["name"] = "content_block";
            $editor_configs["value"] = $this->_obj->getVar('content', 'e');
            $editor_configs["rows"] = 15;
            $editor_configs["cols"] = 6;
            $editor_configs["editor"] = $xoops->getModuleConfig('blocks_editor', 'system');
            $this->addElement(new XoopsFormEditor(XoopsLocale::CONTENT, "content_block", $editor_configs), true);
            if (in_array($editor_configs["editor"], array('dhtmltextarea', 'textarea'))) {
                $ctype_select = new XoopsFormSelect(SystemLocale::CONTENT_TYPE, 'c_type', $this->_obj->getVar('c_type'));
                $ctype_select->addOptionArray(array(
                    'H' => XoopsLocale::HTML,
                    'P' => SystemLocale::PHP_SCRIPT,
                    'S' => SystemLocale::AUTO_FORMAT_SMILIES_ENABLED,
                    'T' => SystemLocale::AUTO_FORMAT_SMILIES_DISABLED
                ));
                $this->addElement($ctype_select);
            } else {
                $this->addElement(new XoopsFormHidden('c_type', 'H'));
            }
        } else {
            if ($this->_obj->getVar('template') != '') {
                $tplfile_handler = $xoops->getHandlerTplfile();
                $btemplate = $tplfile_handler->find($xoops->getConfig('template_set'), 'block', $this->_obj->getVar('bid'));
                if (count($btemplate) > 0) {
                    $this->addElement(new XoopsFormLabel(XoopsLocale::CONTENT, '<a href="' . XOOPS_URL . '/modules/system/admin.php?fct=tplsets&amp;op=edittpl&amp;id=' . $btemplate[0]->getVar('tpl_id') . '">' . SystemLocale::EDIT_TEMPLATE . '</a>'));
                } else {
                    $btemplate2 = $tplfile_handler->find('default', 'block', $this->_obj->getVar('bid'));
                    if (count($btemplate2) > 0) {
                        $this->addElement(new XoopsFormLabel(XoopsLocale::CONTENT, '<a href="' . XOOPS_URL . '/modules/system/admin.php?fct=tplsets&amp;op=edittpl&amp;id=' . $btemplate2[0]->getVar('tpl_id') . '" rel="external">' . SystemLocale::EDIT_TEMPLATE . '</a>'));
                    }
                }
            }
            if ($this->_obj->getOptions() != false) {
                $this->addElement(new XoopsFormLabel(XoopsLocale::OPTIONS, $this->_obj->getOptions()));
            } else {
                $this->addElement(new XoopsFormHidden('options', $this->_obj->getVar('options')));
            }
            $this->addElement(new XoopsFormHidden('c_type', 'H'));
        }
        $cache_select = new XoopsFormSelect(SystemLocale::CACHE_LIFETIME, 'bcachetime', $this->_obj->getVar('bcachetime'));
        $cache_select->addOptionArray(array(
            '0'       => XoopsLocale::NO_CACHE,
            '30'      => sprintf(XoopsLocale::F_SECONDS, 30),
            '60'      => XoopsLocale::ONE_MINUTE,
            '300'     => sprintf(XoopsLocale::F_MINUTES, 5),
            '1800'    => sprintf(XoopsLocale::F_MINUTES, 30),
            '3600'    => XoopsLocale::ONE_HOUR,
            '18000'   => sprintf(XoopsLocale::F_HOURS, 5),
            '86400'   => XoopsLocale::ONE_DAY,
            '259200'  => sprintf(XoopsLocale::F_DAYS, 3),
            '604800'  => XoopsLocale::ONE_WEEK,
            '2592000' => XoopsLocale::ONE_MONTH
        ));
        $this->addElement($cache_select);
        // Groups
        $this->addElement(new XoopsFormSelectGroup(XoopsLocale::GROUPS, 'groups', true, $groups, 5, true));

        $this->addElement(new XoopsFormHidden('block_type', $this->_obj->getVar('block_type')));
        $this->addElement(new XoopsFormHidden('mid', $this->_obj->getVar('mid')));
        $this->addElement(new XoopsFormHidden('func_num', $this->_obj->getVar('func_num')));
        $this->addElement(new XoopsFormHidden('func_file', $this->_obj->getVar('func_file')));
        $this->addElement(new XoopsFormHidden('show_func', $this->_obj->getVar('show_func')));
        $this->addElement(new XoopsFormHidden('edit_func', $this->_obj->getVar('edit_func')));
        $this->addElement(new XoopsFormHidden('template', $this->_obj->getVar('template')));
        $this->addElement(new XoopsFormHidden('dirname', $this->_obj->getVar('dirname')));
        $this->addElement(new XoopsFormHidden('name', $this->_obj->getVar('name')));
        $this->addElement(new XoopsFormHidden('bid', $this->_obj->getVar('bid')));
        $this->addElement(new XoopsFormHidden('op', $op));
        $this->addElement(new XoopsFormHidden('fct', 'blocksadmin'));
        $button_tray = new XoopsFormElementTray('', '&nbsp;');
        if ($this->_obj->isNew() || $this->_obj->isCustom()) {
            $preview = new XoopsFormButton('', 'previewblock', XoopsLocale::A_PREVIEW, 'preview');
            $preview->setExtra("onclick=\"blocks_preview();\"");
            $button_tray->addElement($preview);
        }
        $button_tray->addElement(new XoopsFormButton('', 'submitblock', XoopsLocale::A_SUBMIT, 'submit'));
        $this->addElement($button_tray);
    }
}
