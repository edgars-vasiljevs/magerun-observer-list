<?php

namespace ObserverList;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
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
             ->setDescription('Show all observers for specified event')
             ->addArgument('event', InputArgument::REQUIRED, 'Event code');
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

        $this->_listObservers();
    }

    /**
     * List observers by event code
     */
    protected function _listObservers()
    {
        $event = $this->_input->getArgument('event');

        $table = new Table($this->_output);
        $table->setHeaders(array('Area', 'Type', 'Class', 'Method'));

        $rows = array();
        foreach($this->_areas as $area) {
            $eventConfig = Mage::getConfig()->getEventConfig($area, $event);

            if (!$eventConfig) {
                continue;
            }

            foreach ($eventConfig->observers->children() as $obsName => $obsConfig) {
                $class = $obsConfig->class ? (string)$obsConfig->class : $obsConfig->getClassName();
                $className = Mage::getConfig()->getModelClassName($class);

                $rows[] = array(
                    $area,
                    (string)$obsConfig->type,
                    $className,
                    (string)$obsConfig->method,
                );
            }
        }

        $table->setRows($rows);
        $table->render();
    }
}