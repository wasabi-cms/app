<?php
namespace App\Console;

use Cake\Utility\Security;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Exception;

/**
 * Provides installation hooks for when this application is installed via
 * composer.
 */
class Installer
{
    /**
     * Does some routine installation tasks so people don't have to.
     *
     * @param \Composer\Script\Event $event The composer event object.
     * @throws \Exception Exception raised by validator.
     * @return void
     */
    public static function postInstall(Event $event)
    {
        $io = $event->getIO();

        $rootDir = dirname(dirname(__DIR__));

        // create configuration files
        $baseConfigCreated = static::createEnvironmentBaseConfig($rootDir, $io);
        static::createEnvironmentLocalConfig($rootDir, $io);
        static::createWritableDirectories($rootDir, $io);

        // ask if the permissions should be changed
        if ($io->isInteractive()) {
            $setFolderPermissions = static::askSetFolderPermissions($io);

            if (in_array($setFolderPermissions, ['Y', 'y'])) {
                static::setFolderPermissions($rootDir, $io);
            }
        } else {
            static::setFolderPermissions($rootDir, $io);
        }

        if ($baseConfigCreated) {
            // replace config placeholders with real values
            static::setCachePrefix($rootDir, $io);
            static::setSecuritySalt($rootDir, $io);
            static::setSessionName($rootDir, $io);
            static::setCookieName($rootDir, $io);
        }
    }

    /**
     * Ask the user to automatically set folder permissions.
     *
     * @param IOInterface $io
     * @return string
     */
    public static function askSetFolderPermissions($io)
    {
        $validator = function ($arg) {
            if (in_array($arg, ['Y', 'y', 'N', 'n'])) {
                return $arg;
            }
            throw new Exception('This is not a valid answer. Please choose Y or n.');
        };
        return $io->askAndValidate(
            '<info>Set Folder Permissions ? (Default to Y)</info> [<comment>Y,n</comment>]? ',
            $validator,
            10,
            'Y'
        );
    }

    /**
     * Create the config/Environment/config.php file if it does not exist.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return bool
     */
    public static function createEnvironmentBaseConfig($dir, $io)
    {
        $environmentConfig = $dir . '/config/Environment/config.php';
        $defaultConfig = $dir . '/config/Environment/config.default.php';
        if (!file_exists($environmentConfig)) {
            copy($defaultConfig, $environmentConfig);
            $io->write('Created `config/Environment/config.php` file');
            return true;
        }
        return false;
    }

    /**
     * Create the config/Environment/environment.local.php file if it does not exist.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return bool
     */
    public static function createEnvironmentLocalConfig($dir, $io)
    {
        $environmentConfig = $dir . '/config/Environment/environment.local.php';
        $defaultConfig = $dir . '/config/Environment/environment.local.default.php';
        if (!file_exists($environmentConfig)) {
            copy($defaultConfig, $environmentConfig);
            $io->write('Created `config/Environment/environment.local.php` file');
            return true;
        }
        return false;
    }

    /**
     * Create the `logs` and `tmp` directories.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function createWritableDirectories($dir, $io)
    {
        $paths = [
            'logs',
            'tmp',
            'tmp/cache',
            'tmp/cache/models',
            'tmp/cache/persistent',
            'tmp/cache/views',
            'tmp/cache/wasabi',
            'tmp/cache/wasabi/core',
            'tmp/cache/wasabi/core/group_permissions',
            'tmp/cache/wasabi/core/guardian_paths',
            'tmp/cache/wasabi/core/longterm',
            'tmp/cache/wasabi/core/routes',
            'tmp/sessions',
            'tmp/tests'
        ];

        foreach ($paths as $path) {
            $path = $dir . '/' . $path;
            if (!file_exists($path)) {
                mkdir($path);
                $io->write('Created `' . $path . '` directory');
            }
        }
    }

    /**
     * Set globally writable permissions on the "tmp" and "logs" directory.
     *
     * This is not the most secure default, but it gets people up and running quickly.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function setFolderPermissions($dir, $io)
    {
        sleep(1);
        // Change the permissions on a path and output the results.
        $changePerms = function ($path, $perms, $io) {
            // Get permission bits from stat(2) result.
            $currentPerms = fileperms($path) & 0777;
            if (($currentPerms & $perms) == $perms) {
                return;
            }

            $res = chmod($path, $currentPerms | $perms);
            if ($res) {
                $io->write('Permissions set on ' . $path);
            } else {
                $io->write('Failed to set permissions on ' . $path);
            }
        };

        $walker = function ($dir, $perms, $io) use (&$walker, $changePerms) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;

                if (!is_dir($path)) {
                    continue;
                }

                $changePerms($path, $perms, $io);
                $walker($path, $perms, $io);
            }
        };

        $worldWritable = bindec('0000000111');
        $walker($dir . '/tmp', $worldWritable, $io);
        $changePerms($dir . '/tmp', $worldWritable, $io);
        $changePerms($dir . '/logs', $worldWritable, $io);
    }

    /**
     * Set the security.salt value in the application's config file.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function setSecuritySalt($dir, $io)
    {
        $config = $dir . '/config/Environment/config.php';
        $content = file_get_contents($config);

        $newKey = hash('sha256', Security::randomBytes(64));
        $content = str_replace('___SALT___', $newKey, $content, $count);

        if ($count == 0) {
            $io->write('No Security.salt placeholder to replace.');

            return;
        }

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated Security.salt value in config/Environment/config.php');

            return;
        }
        $io->write('Unable to update Security.salt value.');
    }

    /**
     * Set the cache prefix in /config/Environment/config.php.
     *
     * @param string $dir
     * @param IOInterface $io
     */
    public static function setCachePrefix($dir, $io)
    {
        $config = $dir . '/config/Environment/config.php';
        $content = file_get_contents($config);

        $sessionName = $io->ask(
            '<info>What Cache Prefix should your app use?</info> [<comment>myapp_</comment>]? ',
            'myapp_'
        );

        $content = str_replace('___CACHE_PREFIX___', $sessionName, $content, $count);

        if ($count == 0) {
            $io->write('No ___CACHE_PREFIX___ placeholder to replace.');
            return;
        }

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated Cache Prefix in config/Environment/config.php');

            return;
        }
        $io->write('Unable to update Cache Prefix.');
    }

    /**
     * Set the session name in /config/Environment/config.php.
     *
     * @param string $dir
     * @param IOInterface $io
     */
    public static function setSessionName($dir, $io)
    {
        sleep(1);
        $config = $dir . '/config/Environment/config.php';
        $content = file_get_contents($config);

        $sessionName = $io->ask(
            '<info>What Session Name should your app use?</info> [<comment>myapp</comment>]? ',
            'myapp'
        );

        $content = str_replace('___SESSION_NAME___', $sessionName, $content, $count);

        if ($count == 0) {
            $io->write('No ___SESSION_NAME___ placeholder to replace.');
            return;
        }

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated Session Name in config/Environment/config.php');

            return;
        }
        $io->write('Unable to update Session Name.');
    }

    /**
     * Set the cookie name in /config/Environment/config.php.
     *
     * @param string $dir
     * @param IOInterface $io
     */
    public static function setCookieName($dir, $io)
    {
        sleep(1);
        $config = $dir . '/config/Environment/config.php';
        $content = file_get_contents($config);

        $sessionName = $io->ask(
            '<info>What Cookie Name should your app use?</info> [<comment>myappC</comment>]? ',
            'myappC'
        );

        $content = str_replace('___COOKIE_NAME___', $sessionName, $content, $count);

        if ($count == 0) {
            $io->write('No ___COOKIE_NAME___ placeholder to replace.');
            return;
        }

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated Cookie Name in config/Environment/config.php');

            return;
        }
        $io->write('Unable to update Cookie Name.');
    }
}
