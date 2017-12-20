<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

class FrameManager
{
    private $session;

    /** @var Frame[] */
    private $frames = [];

    /** @var Frame */
    private $mainFrame;

    private $page;

    public function __construct(Session $session, $frameTree, Page $page)
    {
        $this->session = $session;
        $this->page = $page;

        $this->session->on('Page.frameAttached', (new \ReflectionMethod($this, 'onFrameAttached'))->getClosure($this));
        $this->session->on('Page.frameNavigated', (new \ReflectionMethod($this, 'onFrameNavigated'))->getClosure($this));
        $this->session->on('Page.frameDetached', (new \ReflectionMethod($this, 'onFrameDetached'))->getClosure($this));
        $this->session->on('Runtime.executionContextCreated', (new \ReflectionMethod($this, 'onExecutionContextCreated'))->getClosure($this));
        $this->session->on('Runtime.executionContextDestroyed', (new \ReflectionMethod($this, 'onExecutionContextDestroyed'))->getClosure($this));
        $this->session->on('Runtime.executionContextsCleared', (new \ReflectionMethod($this, 'onExecutionContextsCleared'))->getClosure($this));
        $this->session->on('Page.lifecycleEvent', (new \ReflectionMethod($this, 'onLifecycleEvent'))->getClosure($this));

        $this->handleFrameTree($frameTree);
    }

    /**
     * @return Frame|null
     */
    public function getMainFrame()
    {
        return $this->mainFrame;
    }

    private function handleFrameTree($frameTree)
    {
        if (array_key_exists('parentId', $frameTree['frame'])) {
            $this->frameAttached($frameTree['frame']['id'], $frameTree['frame']['parentId']);
        }

        $this->frameNavigated($frameTree['frame']);

        if (array_key_exists('childFrames', $frameTree['frame'])) {
            foreach ($frameTree['frame']['childFrames'] as $childFrameTree) {
                $this->handleFrameTree($frameTree);
            }
        }
    }

    private function frameNavigated($frameData)
    {
        $isMainFrame = !array_key_exists('parentId', $frameData);

        if (array_key_exists($frameData['id'], $this->frames)) {
            return;
        }

        if ($isMainFrame) {
            $this->mainFrame = new Frame($this->session, $this->page, $frameData['id']);
            $this->frames[$frameData['id']] = $this->mainFrame;
        }
    }

    private function frameAttached($frameId, $frameParentId)
    {
        if (array_key_exists($frameId, $this->frames)) {
            return;
        }

        $parentFrame = $this->frames[$frameParentId];
        $this->frames[$frameId] = new Frame($this->session, $this->page, $frameId, $parentFrame);
    }

    private function onFrameAttached($event)
    {
        $this->frameAttached($event['frameId'], $event['parentFrameId']);
    }

    private function onFrameNavigated($event)
    {
        $this->frameNavigated($event['frame']);
    }

    private function onFrameDetached($event)
    {

    }

    private function onExecutionContextCreated($event)
    {
        $context = new ExecutionContext($this->session, $event['context']);

        if ($context->isDefault() && array_key_exists($context->getFrameId(), $this->frames)) {
            $frame = $this->frames[$context->getFrameId()];
            $frame->resolveContext($context);
        }
    }

    private function onExecutionContextDestroyed($event)
    {

    }

    private function onExecutionContextsCleared($event)
    {

    }

    private function onLifecycleEvent($event)
    {

    }
}
