<?php declare(strict_types=1);

namespace WyriHaximus\React\Filesystem\Github;

use ApiClients\Client\Github\AsyncClient;
use ApiClients\Client\Github\AsyncClientInterface;
use ApiClients\Client\Github\Authentication\Anonymous;
use ApiClients\Client\Github\Resource\Async\Contents\Directory as GithubContentsDirectory;
use ApiClients\Client\Github\Resource\Async\Contents\File as GithubContentsFile;
use ApiClients\Client\Github\Resource\Async\Repository;
use ApiClients\Client\Github\Resource\Contents\FileInterface;
use function ApiClients\Foundation\resource_pretty_print;
use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\CallInvokerInterface;
use React\Filesystem\Filesystem;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\Node\Directory as ReactDirectory;
use React\Filesystem\Node\File as ReactFile;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\NotSupportedException;
use React\Filesystem\ObjectStream;
use React\Promise\Promise;
use function React\Promise\reject;

final class RepositoryAdapter implements AdapterInterface
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var CallInvokerInterface
     */
    private $invoker;

    /**
     * @var Repository
     */
    private $repository;

    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;
        $this->options = $options;

        $this->repository = $this->options['repository'];

        //$this->setUpGithubClient();
    }

    public static function isSupported()
    {
        return true;
    }

    private function setUpGithubClient()
    {
        if (isset($this->options['github']) && $this->options['github'] instanceof AsyncClientInterface) {
            $this->github = $this->options['github'];
            return;
        }

        $this->options['github'] = AsyncClient::create($this->loop, new Anonymous());
    }

    public function getLoop()
    {
        return $this->loop;
    }

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function setInvoker(CallInvokerInterface $invoker)
    {
        $this->invoker = $invoker;
    }

    public function callFilesystem($function, $args, $errorResultCode = -1)
    {
        // TODO: Implement callFilesystem() method.
    }

    public function mkdir($path, $mode = self::CREATION_MODE)
    {
        return reject(new NotSupportedException());
    }

    public function rmdir($path)
    {
        return reject(new NotSupportedException());
    }

    public function unlink($filename)
    {
        return reject(new NotSupportedException());
    }

    public function chmod($path, $mode)
    {
        return reject(new NotSupportedException());
    }

    public function chown($path, $uid, $gid)
    {
        return reject(new NotSupportedException());
    }

    public function stat($filename)
    {
        return new Promise(function ($resolve, $reject) use ($filename) {
            $stat = [];
            $this->repository->contents($filename)->subscribe(
                function ($node) use (&$stat) {
                    $stat[] = [
                        'type' => $node->type(),
                        'size' => $node instanceof FileInterface ? $node->size() : null,
                    ];
                },
                $reject,
                function () use ($resolve, &$stat) {
                    $count = \count($stat);
                    if ($count === 0) {
                        $resolve(false);

                        return;
                    }

                    if ($count === 1) {
                        $resolve($stat[0]);

                        return;
                    }

                    $resolve([
                        'type' => 'dir',
                        'size' => null,
                    ]);
                }
            );
        });
    }

    public function ls($path)
    {
        $stream = new ObjectStream();
        $this->loop->futureTick(function () use ($stream, $path) {
            $this->repository->contents($path)->subscribe(
                function ($node) use ($stream) {
                    if ($node instanceof GithubContentsFile) {
                        $stream->write(new ReactFile($node->path(), $this->filesystem));
                    }

                    if ($node instanceof GithubContentsDirectory) {
                        $stream->write(new ReactDirectory($node->path(), $this->filesystem));
                    }
                },
                function ($error) use ($stream) {
                    $stream->emit('error', [$error]);
                },
                [$stream, 'end']
            );
        });
        return $stream;
    }

    public function touch($path, $mode = self::CREATION_MODE)
    {
        return reject(new NotSupportedException());
    }

    public function open($path, $flags, $mode = self::CREATION_MODE)
    {
        // TODO: Implement open() method.
    }

    public function read($fileDescriptor, $length, $offset)
    {
        return reject(new NotSupportedException());
    }

    public function write($fileDescriptor, $data, $length, $offset)
    {
        return reject(new NotSupportedException());
    }

    public function close($fd)
    {
        return reject(new NotSupportedException());
    }

    public function rename($fromPath, $toPath)
    {
        return reject(new NotSupportedException());
    }

    public function readlink($path)
    {
        return reject(new NotSupportedException());
    }

    public function symlink($fromPath, $toPath)
    {
        return reject(new NotSupportedException());
    }

    public function detectType($path)
    {
        return reject(new NotSupportedException());
    }
}
