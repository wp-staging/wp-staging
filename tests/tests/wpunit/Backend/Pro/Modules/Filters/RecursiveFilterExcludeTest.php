<?php

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use WPStaging\Iterators\RecursiveDirectoryIterator;
use WPStaging\Backend\Pro\Modules\Filters\RecursiveFilterExclude;

class RecursiveFilterExcludeTest extends \Codeception\TestCase\WPTestCase
{
    /** @var vfsStreamDirectory */
    protected $root;

    public function setUp(): void
    {
        $this->root = vfsStream::setup();

        // The virtual file system
        $structure = array(
            'wp-content' => array(
                'plugins' => array(
                    'wpstg-tmp-woocommerce' => array(
                        'wpstg-tmp-test.php' => 'content',
                        'wpstg-tmp-other.php' => 'content',
                        'wpstg-tmp-Invalid.csv' => 'content',
                    ),
                    'wpstg-bak-woocommerce' => array(
                        'wpstg-bak-test.php1' => 'content',
                        'wpstg-bak-other.php' => 'content',
                        'wpstg-bak-Invalid.csv' => 'content',
                    ),
                    'my-plugin' => array(
                        'index.php',
                        'library' => array(
                            'class-my-plugin1.php' => 'content',
                            'class-my-plugin2.php' => 'content'
                        )
                    ),
                    'my-second-plugin2' => array(
                        'index.php',
                        'library' => array(
                            'class-second-plugin1.php' => 'content',
                            'class-second-plugin2.php' => 'content'
                        )
                    ),
                )
            )
        );

        vfsStream::create($structure);

        parent::setUp();

    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testRecursiveIteratorIteratorExcludeFolders()
    {

        $path = $this->root->url() . '/wp-content/plugins/';

        $iterator = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveFilterExclude($iterator, apply_filters('wpstg_push_excl_folders_custom', array()));
        $iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);



        $data = [];
        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $data[] = $item->getFileName();
            }

        }

        $this->assertContains('class-my-plugin1.php', $data);
        $this->assertContains('class-my-plugin2.php', $data);
        $this->assertContains('class-second-plugin1.php', $data);
        $this->assertContains('class-second-plugin2.php', $data);
        $this->assertNotContains('wpstg-tmp-test.php', $data);
        $this->assertNotContains('wpstg-bak-other.php', $data);
        $this->assertNotContains('wpstg-bak-invalid.php', $data);
        $this->assertNotContains('wpstg-tmp-test.php', $data);
        $this->assertNotContains('wpstg-tmp-other.php', $data);
        $this->assertNotContains('wpstg-tmp-invalid.php', $data);

    }
}
