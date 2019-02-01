<?php declare(strict_types=1);

use ApiClients\Client\Github\AsyncClient;
use ApiClients\Client\Github\Authentication\Anonymous;
use ApiClients\Client\Github\Resource\Async\Repository;
use ApiClients\Client\Github\Resource\Async\User;
use function ApiClients\Foundation\resource_pretty_print;
use React\EventLoop\Factory;
use React\Filesystem\Filesystem;
use WyriHaximus\React\Filesystem\Github\RepositoryAdapter;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$github = AsyncClient::create($loop, new Anonymous());

$github->user('WyriHaximus')->then(function (User $user) {
    return $user->repository('reactphp-filesystem-github');
})->then(function (Repository $repository) use ($loop) {
    $options = [
        'repository' => $repository,
    ];
    $adapter = new RepositoryAdapter($loop, $options);
    return Filesystem::createFromAdapter($adapter);
})->then(function (Filesystem $filesystem) {
    return $filesystem->file('/')->stat();
})->done(function ($stat) {
    var_export($stat);
});

$loop->run();