<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

namespace Resp;

use Resp\Core, Resp\FileManager;

defined('RESP_TEXT_DOMAIN') or die;

class Component
{

    /**
     * @since 0.9.0
     */
    function getName()
    {
        $path = explode('\\', get_class($this));

        return array_pop($path);
    }

    /**
     * @since 0.9.0
     */
    static function getInstance($name)
    {
        return Core::getComponent($name);
    }


    /**
     * @since 0.9.0
     */
    function getAssetsUri($path)
    {
        $name = $this->getName();

        return FileManager::getRespComponentsDirectoryUri("{$name}/assets/$path");
    }

    /**
     * @since 0.9.0
     */
    static function register()
    {
        $instance = new static();

        $name = $instance->getName();

        Core::registerComponent($name, $instance);
    }
}
