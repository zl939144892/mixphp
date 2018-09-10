<?php

namespace apps\httpd\commands;

use mix\console\Command;
use mix\console\ExitCode;
use mix\facades\Output;
use mix\helpers\ProcessHelper;

/**
 * Service 命令
 * @author 刘健 <coder.liu@qq.com>
 */
class ServiceCommand extends Command
{

    // 是否后台运行
    public $daemon = false;

    // 是否热更新
    public $update = false;

    // PID 文件
    protected $pidFile;

    // 选项配置
    public function options()
    {
        return ['daemon', 'update'];
    }

    // 选项别名配置
    public function optionAliases()
    {
        return ['d' => 'daemon', 'u' => 'update'];
    }

    // 初始化事件
    public function onInitialize()
    {
        parent::onInitialize(); // TODO: Change the autogenerated stub
        // 设置pidfile
        $this->pidFile = '/var/run/mix-httpd.pid';
    }

    // 启动服务
    public function actionStart()
    {
        if ($pid = ProcessHelper::readPidFile($this->pidFile)) {
            Output::writeln("mix-httpd is running, PID : {$pid}.");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $server = \mix\http\HttpServer::newInstanceByConfig('httpServer');
        if ($this->update) {
            $server->settings['max_request'] = 1;
        }
        $server->settings['daemonize'] = $this->daemon;
        $server->settings['pid_file']  = $this->pidFile;
        $server->start();
        // 返回退出码
        return ExitCode::OK;
    }

    // 停止服务
    public function actionStop()
    {
        if ($pid = ProcessHelper::readPidFile($this->pidFile)) {
            ProcessHelper::kill($pid);
            while (ProcessHelper::isRunning($pid)) {
                // 等待进程退出
                usleep(100000);
            }
            Output::writeln('mix-httpd stop completed.');
        } else {
            Output::writeln('mix-httpd is not running.');
        }
        // 返回退出码
        return ExitCode::OK;
    }

    // 重启服务
    public function actionRestart()
    {
        $this->actionStop();
        $this->actionStart();
        // 返回退出码
        return ExitCode::OK;
    }

    // 重启工作进程
    public function actionReload()
    {
        if ($pid = ProcessHelper::readPidFile($this->pidFile)) {
            ProcessHelper::kill($pid, SIGUSR1);
        }
        if (!$pid) {
            Output::writeln('mix-httpd is not running.');
            return ExitCode::UNSPECIFIED_ERROR;
        }
        Output::writeln('mix-httpd worker process restart completed.');
        // 返回退出码
        return ExitCode::OK;
    }

    // 查看服务状态
    public function actionStatus()
    {
        if ($pid = ProcessHelper::readPidFile($this->pidFile)) {
            Output::writeln("mix-httpd is running, PID : {$pid}.");
        } else {
            Output::writeln('mix-httpd is not running.');
        }
        // 返回退出码
        return ExitCode::OK;
    }

}
