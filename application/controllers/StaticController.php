<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\ActionController;
use Icinga\Application\Icinga,
    Zend_Controller_Action_Exception as ActionException;

class StaticController extends ActionController
{

    protected $handlesAuthentication = true;

    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
    }

    private function getModuleList()
    {
        $modules = Icinga::app()->getModuleManager()->getLoadedModules();

        // preliminary static definition
        $result = array();
        foreach ($modules as $name => $module) {
            $hasJs = file_exists($module->getBasedir() . "/public/js/$name.js");
            $result[] = array(
                'name'      => $name,
                'active'    => true,
                'type'      => 'generic',
                'behaviour' => $hasJs
            );
        }
        return $result;
    }

    public function modulelistAction()
    {

        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
        $this->getResponse()->setHeader("Content-Type","application/json");
        echo "define(function() { return ".json_encode($this->getModuleList(),true)."; })";
        exit;
    }

    public function imgAction()
    {
        $module = $this->_getParam('moduleName');
        $file   = $this->_getParam('file');
        $basedir = Icinga::app()->getModuleManager()->getModule($module)->getBaseDir();

        $filePath = $basedir . '/public/img/' . $file;
        if (! file_exists($filePath)) {
            throw new ActionException(sprintf(
                '%s does not exist',
                $filePath
            ), 404);
        }
        if (preg_match('/\.([a-z]+)$/i', $file, $m)) {
            $extension = $m[1];
        } else {
            $extension = 'fixme';
        }
        $hash = md5_file($filePath);
        if ($hash === $this->getRequest()->getHeader('If-None-Match')) {
            $this->getResponse()->setHttpResponseCode(304);
            return;
        }
        header('ETag: ' . $hash);
        header('Content-Type: image/' . $extension);
        header('Cache-Control: max-age=3600');
        header('Last-Modified: ' . gmdate(
            'D, d M Y H:i:s',
            filemtime($filePath)
        ) . ' GMT');

        readfile($filePath);
        $this->_viewRenderer->setNoRender();
    }

    public function javascriptAction()
    {
        $module = $this->_getParam('module_name');
        $file   = $this->_getParam('file');

        $basedir = Icinga::app()->getModuleManager()->getModule($module)->getBaseDir();

        $filePath = $basedir . '/public/js/' . $file;
        if (!file_exists($filePath)) {
            throw new ActionException(
                sprintf(
                    '%s does not exist',
                    $filePath
                ),
                404
            );
        }
        $hash = md5_file($filePath);
        $response = $this->getResponse();
        $response->setHeader('ETag', $hash);
        $response->setHeader('Content-Type', 'application/javascript');
        $response->setHeader('Cache-Control', 'max-age=3600', true);
        $response->setHeader(
            'Last-Modified',
            gmdate(
                'D, d M Y H:i:s',
                filemtime($filePath)
            ) . ' GMT'
        );

        $hash = md5_file($filePath);

        if ($hash === $this->getRequest()->getHeader('If-None-Match')) {
            $response->setHttpResponseCode(304);
            return;
        } else {
            readfile($filePath);
        }
        $this->_viewRenderer->setNoRender();
    }

}

// @codingStandardsIgnoreEnd
