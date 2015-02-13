<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\App\View\Asset;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverPool;

class PublisherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\App\State|\PHPUnit_Framework_MockObject_MockObject
     */
    private $appState;

    /**
     * @var \Magento\Framework\Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rootDirWrite;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $staticDirRead;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $staticDirWrite;

    /**
     * @var \Magento\Framework\App\View\Asset\Publisher
     */
    private $object;

    protected function setUp()
    {
        $this->appState = $this->getMock('Magento\Framework\App\State', [], [], '', false);
        $this->filesystem = $this->getMock('Magento\Framework\Filesystem', [], [], '', false);
        $this->object = new Publisher($this->appState, $this->filesystem);

        $this->rootDirWrite = $this->getMockForAbstractClass('Magento\Framework\Filesystem\Directory\WriteInterface');
        $this->staticDirRead = $this->getMockForAbstractClass('Magento\Framework\Filesystem\Directory\ReadInterface');
        $this->staticDirWrite = $this->getMockForAbstractClass('Magento\Framework\Filesystem\Directory\WriteInterface');
        $this->filesystem->expects($this->any())
            ->method('getDirectoryRead')
            ->with(DirectoryList::STATIC_VIEW)
            ->will($this->returnValue($this->staticDirRead));
        $this->filesystem->expects($this->any())
            ->method('getDirectoryWrite')
            ->will($this->returnValueMap([
                [DirectoryList::ROOT, DriverPool::FILE, $this->rootDirWrite],
                [DirectoryList::STATIC_VIEW, DriverPool::FILE, $this->staticDirWrite],
            ]));
    }

    public function testPublishNotAllowed()
    {
        $this->appState->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(\Magento\Framework\App\State::MODE_DEVELOPER));

        $asset = $this->getMock('Magento\Framework\View\Asset\File', [], [], '', false);
        $asset->expects($this->never())
            ->method('getPath')
            ->will($this->returnValue('some/file.ext'));
        $asset->expects($this->never())
            ->method('getContent')
            ->will($this->returnValue('content'));

        $this->assertFalse($this->object->publish($asset));
    }

    public function testPublishExistsBefore()
    {
        $this->appState->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(\Magento\Framework\App\State::MODE_PRODUCTION));
        $this->staticDirRead->expects($this->once())
            ->method('isExist')
            ->with('some/file.ext')
            ->will($this->returnValue(true));

        $asset = $this->getMock('Magento\Framework\View\Asset\File', [], [], '', false);
        $asset->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('some/file.ext'));
        $asset->expects($this->never())
            ->method('getContent')
            ->will($this->returnValue('content'));

        $this->assertTrue($this->object->publish($asset));
    }

    public function testPublish()
    {
        $this->appState->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(\Magento\Framework\App\State::MODE_PRODUCTION));
        $this->staticDirRead->expects($this->once())
            ->method('isExist')
            ->with('some/file.ext')
            ->will($this->returnValue(false));

        $this->staticDirWrite->expects($this->once())
            ->method('writeFile')
            ->with('some/file.ext', 'content')
            ->will($this->returnValue(true));

        $asset = $this->getMock('Magento\Framework\View\Asset\File', [], [], '', false);
        $asset->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('some/file.ext'));
        $asset->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue('content'));

        $this->object->publish($asset);
    }
}
