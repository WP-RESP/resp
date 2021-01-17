<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

use Resp\Core, Resp\FileManager;

defined('RESP_VERSION') or die;

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
