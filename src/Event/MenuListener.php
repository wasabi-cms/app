<?php

namespace App\Event;

use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Wasabi\Core\Navigation\Menu;

class MenuListener implements EventListenerInterface
{
    /**
     * Returns a list of events this object is implementing.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Wasabi.Backend.Menu.initMain' => [
                'callable' => 'initBackendMenuMainItems',
                'priority' => 2000
            ]
        ];
    }

    /**
     * Initialize the main backend menu items.
     *
     * @param Event $event The Wasabi.Backend.Menu.initMain event that was fired.
     * @return void
     */
    public function initBackendMenuMainItems(Event $event)
    {
        /** @var Menu $menu */
        $menu = $event->getSubject();

        // Add/remove menu items here by using methods on the $menu object.
    }
}
