<?php

namespace App\Services\Aws;

use Aws\Ec2\Ec2Client;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;


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
}
