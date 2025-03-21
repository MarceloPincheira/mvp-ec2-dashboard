<?php

namespace App\Services\Aws;

use Aws\Ec2\Ec2Client;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Carbon\Carbon;


class Ec2Service
{
    protected $client;

    public function __construct()
    {
        $this->client = new Ec2Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    public function getInstances(): void
    {
        $result = $this->client->describeInstances();

        foreach ($result['Reservations'] as $reservation) {
            foreach ($reservation['Instances'] as $instance) {
                echo "InstanceId: {$instance['InstanceId']} - {$instance['State']['Name']} \n";
            }
        }
    }

    public function getPublicIp(): void
    {
        $result = $this->client->describeInstances();

        foreach ($result['Reservations'] as $reservation) {
            foreach ($reservation['Instances'] as $instance) {
                echo "InstanceId: {$instance['InstanceId']}: {$instance['PublicIpAddress']} \n";
            }
        }
    }

    public function getLastAccessDate()
    {
        $ssh = new SSH2(env('AWS_EC2_INSTANCE_IP_ADDRESS'));
        $key = PublicKeyLoader::load(file_get_contents(storage_path(env('SSH_PRIVATE_KEY_PATH'))));

        if (!$ssh->login('ubuntu', $key)) {
            return 'SSH Failed';
        }

        $logPath = '/var/log/apache2/access.log';
        $projectsDir = '/var/www';
        $command = "cd {$projectsDir} && find . -type d -maxdepth 1";

        $folders = $ssh->exec($command);
        $projects = explode("\n", trim($folders));

        $lastAccesses = [];

        foreach ($projects as $project) {
            $url = ltrim($project, './');

            // Busca las últimas peticiones a la URL del proyecto en los logs
            $command = "sudo grep '{$url}' {$logPath} | tail -n 1 | awk '{print \$4, \$5}'";
            $lastAccess = $ssh->exec($command);

            if ($lastAccess) {
                $lastAccesses[$project] = "Last access {$this->formatDate(trim($lastAccess))}";
            } else {
                $lastAccesses[$project] = 'No Access Logs';
            }
        }

        return $lastAccesses;
    }

    public function formatDate($dateString)
    {
        $cleanedDateString = trim($dateString, '[]');

        return Carbon::createFromFormat('d/M/Y:H:i:s O', $cleanedDateString);

    }
}
