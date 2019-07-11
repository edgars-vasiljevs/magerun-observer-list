<?php

namespace ObserverList;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ListCommand extends AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $output  */
    protected $_output;

    /**
     * Magento areas
     *
     * @var array
     */
    protected $_areas = array(
        'global',
        'frontend',
        'admin',
        'adminhtml',
    );

    protected function configure()
    {
        $this->setName('scandi:observer-list')
             ->setDescription('Show all observers for all events or for specified event')
             ->addArgument('event', InputArgument::OPTIONAL, 'Event code')
             ->addOption('exclude-core', null, InputOption::VALUE_NONE, 'Exclude Magento core observers');
    }

   /**
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $this->detectMagento($output);

        if (!$this->initMagento()) {
            return;
        }

        $event = $this->_input->getArgument('event');
        $excludeCore = $this->_input->getOption('exclude-core');

        if (!$event) {
            $header = array('Event', 'Area', 'Type', 'Class', 'Method');
            $rows = $this->_getAllObservers($excludeCore);
            usort($rows, function ($a, $b) {
                return strcmp($a[0], $b[0]);
            });
        }
        else {
            $header = array('Area', 'Type', 'Class', 'Method');
            $rows = $this->_getEventObservers($event, $excludeCore);
        }

        $table = new Table($this->_output);
        $table->setHeaders($header);
        $table->setRows($rows);
        $table->render();
    }

    /**
     * Get all observers for all events
     *
     * @param bool $excludeCore
     * @return array
     */
    protected function _getAllObservers($excludeCore = false)
    {
        $rows = array();

        foreach($this->_areas as $area) {
            $events = Mage::getConfig()->getNode($area . '/events');

            if ($events === false) {
                continue;
            }

            $events = $events->asArray();

            foreach($events as $key => $item) {
                foreach($item['observers'] as $observer) {

                    $className = Mage::getConfig()->getModelClassName($observer['class']);

                    if ($excludeCore && $this->_isCoreMagentoObserver($className)) {
                        continue;
                    }

                    $rows[] = array(
                        $key,
                        $area,
                        $observer['type'],
                        $className,
                        $observer['method'],
                    );
                }
            }
        }

        return $rows;
    }

    /**
     * List observers by event code
     *
     * @param string $event
     * @param bool $excludeCore
     * @return array
     */
    protected function _getEventObservers($event, $excludeCore = false)
    {
        $rows = array();

        foreach($this->_areas as $area) {
            $eventConfig = Mage::getConfig()->getEventConfig($area, $event);

            if (!$eventConfig) {
                continue;
            }

            foreach ($eventConfig->observers->children() as $obsName => $obsConfig) {
                $class = $obsConfig->class ? (string)$obsConfig->class : $obsConfig->getClassName();
                $className = Mage::getConfig()->getModelClassName($class);

                if ($excludeCore && $this->_isCoreMagentoObserver($className)) {
                    continue;
                }

                $rows[] = array(
                    $area,
                    (string)$obsConfig->type,
                    $className,
                    (string)$obsConfig->method,
                );
            }
        }

        return $rows;
    }

    /**
     * @param $className
     * @return bool
     */
    protected function _isCoreMagentoObserver($className)
    {
        return substr($className, 0, 5) == 'Mage_' || substr($className, 0, 11) == 'Enterprise_';
    }
}