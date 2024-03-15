<?php

namespace Ledc\Likeadmin\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Ledc\Likeadmin\generate_nginx_proxy_config;

/**
 * 生成nginx配置文件
 */
class NginxConfig extends Command
{
    /**
     * @var string
     */
    protected static string $defaultName = 'likeadmin:nginx';
    /**
     * @var string
     */
    protected static string $defaultDescription = '生成nginx配置文件';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::OPTIONAL, '生成nginx配置文件', 'likeadmin_proxy');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('filename') . date('YmdH');
        $conf = generate_nginx_proxy_config();
        $filename = base_path($name . '.conf');
        file_put_contents($filename, $conf);
        $output->writeln('成功生成nginx配置文件：' . $filename);
        return self::SUCCESS;
    }
}
