<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/23
 * Time: 10:30
 */

namespace GoSwoole\Plugins\Mysql;

use GoSwoole\BaseServer\Plugins\Logger\GetLogger;
use GoSwoole\BaseServer\Server\Context;
use GoSwoole\BaseServer\Server\Plugin\AbstractPlugin;
use GoSwoole\BaseServer\Server\Server;

class MysqlPlugin extends AbstractPlugin
{
    use GetLogger;
    /**
     * @var MysqlConfig[]
     */
    protected $configList = [];

    /**
     * 获取插件名字
     * @return string
     */
    public function getName(): string
    {
        return "Mysql";
    }

    /**
     * 在服务启动前
     * @param Context $context
     * @return mixed
     * @throws \GoSwoole\BaseServer\Server\Exception\ConfigException
     */
    public function beforeServerStart(Context $context)
    {
        //所有配置合併
        foreach ($this->configList as $config) {
            $config->merge();
        }
        return;
    }

    /**
     * 在进程启动前
     * @param Context $context
     * @return mixed
     * @throws \GoSwoole\BaseServer\Exception
     * @throws \ReflectionException
     */
    public function beforeProcessStart(Context $context)
    {
        $mysqlManyPool = new MysqlManyPool();
        //重新获取配置
        $this->configList = [];
        $configs = Server::$instance->getConfigContext()->get(MysqlConfig::key, []);
        if (empty($configs)) {
            $this->warn("没有mysql配置");
        }
        foreach ($configs as $key => $value) {
            $mysqlConfig = new MysqlConfig("", "", "", "");
            $mysqlConfig->setName($key);
            $this->configList[$key] = $mysqlConfig->buildFromConfig($value);
            $mysqlPool = new MysqlPool($mysqlConfig);
            $mysqlManyPool->addPool($mysqlPool);
            $this->debug("已添加名为 {$mysqlConfig->getName()} 的Mysql连接池");
        }
        $context->add("mysqlPool", $mysqlManyPool);
        $this->setToDIContainer(MysqlManyPool::class,$mysqlManyPool);
        $this->setToDIContainer(MysqlPool::class,$mysqlManyPool->getPool());
        $this->ready();
    }

    /**
     * @return MysqlConfig[]
     */
    public function getConfigList(): array
    {
        return $this->configList;
    }

    /**
     * @param MysqlConfig[] $configList
     */
    public function setConfigList(array $configList): void
    {
        $this->configList = $configList;
    }

    /**
     * @param MysqlConfig $mysqlConfig
     */
    public function addConfigList(MysqlConfig $mysqlConfig): void
    {
        $this->configList[$mysqlConfig->getName()] = $mysqlConfig;
    }
}