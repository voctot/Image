<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * ConflictHelper.php
 * Author     : Hran
 * Date       : 2020/03/12
 * Version    :
 * Description:
 */
class Mirages_ConflictHelper {
    private static $conflictHooks = array(
        'Widget_Contents_Post_Edit:write',
        'Widget_Contents_Page_Edit:write',
        'Widget_Abstract_Contents:content',
        'Widget_Abstract_Contents:excerpt',
        'Widget_Abstract_Contents:contentEx',
        'Widget_Abstract_Contents:excerptEx',
    );

    private static $usedHooks = array();
    private static $failedPlugins = array();
    private static $currentPlugin = FALSE;
    private static $currentChanged = 0;

    public static function loadConflictedPlugins() {
        $conflicted = array();
        $plugins = Typecho_Plugin::export();
        $activated = @$plugins["activated"];
        if (empty($activated)) {
            return $conflicted;
        }
        $activatedPlugins = array_keys($activated);
        foreach ($activatedPlugins as $pluginName) {

            if ($pluginName === 'Mirages') {
                continue;
            }

            $handles = @$activated[$pluginName]['handles'];
            if (!empty($handles)) {
                $hooks = array_keys($handles);
                foreach ($hooks as $hook) {
                    if (in_array($hook, self::$conflictHooks)) {
                        $conflicted[] = $pluginName;
                    }
                }
            }
        }

        $conflicted = array_unique($conflicted);
        return $conflicted;
    }

    private static function loadUsedHooks() {
        $plugins = Typecho_Plugin::export();
        $activated = @$plugins["activated"];
        if (empty($activated)) {
            return;
        }
        $activatedPlugins = array_keys($activated);
        foreach ($activatedPlugins as $pluginName) {
            if ($pluginName === 'Mirages') {
                continue;
            }

            $handles = @$activated[$pluginName]['handles'];
            if (!empty($handles)) {
                $hooks = array_keys($handles);
                foreach ($hooks as $hook) {
                    if (in_array($hook, array(
                        'Mirages_Plugin:writePost',
                        'Mirages_Plugin:writePage',
                        'Mirages_Plugin:content',
                        'Mirages_Plugin:excerpt',
                        'Mirages_Plugin:contentEx',
                        'Mirages_Plugin:excerptEx',
                        'Mirages_Plugin:writePost2',
                        'Mirages_Plugin:writePage2',
                        'Mirages_Plugin:content2',
                        'Mirages_Plugin:excerpt2',
                        'Mirages_Plugin:contentEx2',
                        'Mirages_Plugin:excerptEx2'
                    ))) {
                        self::$usedHooks[] = $hook;
                    }
                }
            }
        }
    }

    public static function resolveConflict() {
        Mirages_Utils::log("开始处理插件冲突...");

        $conflictedPlugins = self::loadConflictedPlugins();
        if (empty($conflictedPlugins)) {
            Mirages_Utils::log("没有检测到插件冲突", Mirages_Utils::LOG_LEVEL_SUCCESS);
            return array(
                "status" => "success",
                "message" => "没有检测到插件冲突"
            );
        }

        $hasFalse = false;
        Mirages_Utils::log("检测到冲突: " . join(", ", $conflictedPlugins));
        self::loadUsedHooks();
        foreach ($conflictedPlugins as $conflictedPlugin) {
            Mirages_Utils::log("正在处理插件: " . $conflictedPlugin);
            $ret = self::doResolveConflictedPlugin($conflictedPlugin);
            if ($ret !== true) {
                $hasFalse = true;
            }
        }


        if ($hasFalse) {
            Mirages_Utils::log("部分插件处理出错，请查看详细日志", Mirages_Utils::LOG_LEVEL_ERROR);
            Mirages_Utils::log("部分插件处理出错，请查看详细日志", Mirages_Utils::LOG_LEVEL_ERROR);
            Mirages_Utils::log("部分插件处理出错，请查看详细日志", Mirages_Utils::LOG_LEVEL_ERROR);
            Mirages_Utils::log("重要的事情说三遍", Mirages_Utils::LOG_LEVEL_ERROR);
            return array(
                "status" => "error",
                "message" => "部分插件处理出错，请查看详细日志"
            );
        } else {
            return array(
                "status" => "success",
                "message" => "插件冲突处理完成，请**禁用后重启**仍然提示冲突的插件"
            );
        }

    }


    private static function doResolveConflictedPlugin($pluginName) {
        try {
            $pluginFile = Typecho_Plugin::portal($pluginName, Mirages_Utils::pluginsDir())[0];
            if (!Mirages_Utils::is_really_writable($pluginFile)) {
                Mirages_Utils::log("插件: " . $pluginFile . "没有写入权限", Mirages_Utils::LOG_LEVEL_ERROR);
                return false;
            }
            self::$currentPlugin = $pluginName;
            self::$currentChanged = 0;
            $content = file_get_contents($pluginFile);
            if ($content) {
                $content = preg_replace_callback('/Typecho_Plugin\s*::\s*factory\s*\(\s*\'([A-Za-z0-9_@\/.\-]+)\'\s*\)->([a-zA-Z0-9_]+)\s*=/i', array('Mirages_ConflictHelper', '_doReplacePluginContent'), $content);
                if (in_array($pluginName, self::$failedPlugins)) {
                    Mirages_Utils::log("插件冲突相同 HOOK 处理数量超出限制", Mirages_Utils::LOG_LEVEL_ERROR);
                    self::$currentPlugin = FALSE;
                    self::$currentChanged = 0;
                    return false;
                }
                if (self::$currentChanged > 0) {
                    file_put_contents($pluginFile, $content);
                }
            }
        } catch (Exception $e) {
            Mirages_Utils::log($e, Mirages_Utils::LOG_LEVEL_ERROR);
            self::$currentPlugin = FALSE;
            self::$currentChanged = 0;
            return false;
        }
        self::$currentPlugin = FALSE;
        self::$currentChanged = 0;
        return true;
    }

    private static function _doReplacePluginContent($matches) {
        $hook = $matches[1] . ":" . $matches[2];
        if (!in_array($hook, self::$conflictHooks)) {
            return $matches[0];
        }

        $hookFunction = $matches[2];
        if ($hook == "Widget_Contents_Post_Edit:write") {
            $hookFunction = "writePost";
        } elseif ($hook == "Widget_Contents_Page_Edit:write") {
            $hookFunction = "writePage";
        }
        if (!in_array("Mirages_Plugin:" . $hookFunction, self::$usedHooks)) {
            $newHook = $hookFunction;
        } elseif (!in_array("Mirages_Plugin:" . $hookFunction . "2", self::$usedHooks)) {
            $newHook = $hookFunction . "2";
        } else {
            self::$failedPlugins[] = self::$currentPlugin;
            return $matches[0];
        }

        self::$usedHooks[] = "Mirages_Plugin:" . $newHook;
        self::$currentChanged ++;

        return "Typecho_Plugin::factory('Mirages_Plugin')->" . $newHook . " = ";
    }
}