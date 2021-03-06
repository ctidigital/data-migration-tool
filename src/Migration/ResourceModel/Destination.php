<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Migration\ResourceModel;

use \Migration\Config;

/**
 * ResourceModel destination class
 */
class Destination extends AbstractResource
{
    const CONFIG_DOCUMENT_PREFIX = 'dest_prefix';

    /**
     * @var string
     */
    protected $documentPrefix;

    /**
     * Save data into destination resource
     *
     * @param string $documentName
     * @param \Migration\ResourceModel\Record\Collection|array $records
     * @param bool|array $updateOnDuplicate
     * @return $this
     */
    public function saveRecords($documentName, $records, $updateOnDuplicate = false)
    {
        $pageSize = $this->getPageSize($documentName);
        $i = 0;
        $data = [];
        $documentName = $this->addDocumentPrefix($documentName);
        /** @var \Migration\ResourceModel\Record|array $row */
        foreach ($records as $row) {
            $i++;
            if ($row instanceof \Migration\ResourceModel\Record) {
                $data[] = $row->getData();
            } else {
                $data[] = $row;
            }
            if ($i == $pageSize) {
                $this->getAdapter()->insertRecords($documentName, $data, $updateOnDuplicate);
                $data = [];
                $i = 0;
            }
        }
        if ($i > 0) {
            $this->getAdapter()->insertRecords($documentName, $data, $updateOnDuplicate);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResourceConfig()
    {
        $destination = $this->configReader->getDestination();
        $destinationType = $destination['type'];
        $config['database']['host'] = $destination[$destinationType]['host'];
        $config['database']['dbname'] = $destination[$destinationType]['name'];
        $config['database']['username'] = $destination[$destinationType]['user'];
        $config['database']['password'] = !empty($destination[$destinationType]['password'])
            ? $destination[$destinationType]['password']
            : '';
        $initStatements = $this->configReader->getOption('init_statements_destination');
        if (!empty($initStatements)) {
            $config['database']['initStatements'] = $initStatements;
        }
        $editionMigrate = $this->configReader->getOption('edition_migrate');
        if (in_array($editionMigrate, [Config::EDITION_MIGRATE_CE_TO_EE, Config::EDITION_MIGRATE_EE_TO_EE])) {
            $config['init_select_parts'] = ['disable_staging_preview' => true];
        }
        return $config;
    }

    /**
     * @param string $documentName
     * @return void
     */
    public function clearDocument($documentName)
    {
        $this->getAdapter()->deleteAllRecords($this->addDocumentPrefix($documentName));
    }

    /**
     * {@inheritdoc}
     */
    protected function getDocumentPrefix()
    {
        if (null === $this->documentPrefix) {
            $this->documentPrefix = $this->configReader->getOption(self::CONFIG_DOCUMENT_PREFIX);
        }
        return $this->documentPrefix;
    }

    /**
     * @param string $documentName
     * @return void
     */
    public function backupDocument($documentName)
    {
        $this->getAdapter()->backupDocument($this->addDocumentPrefix($documentName));
    }

    /**
     * @param string $documentName
     * @return void
     */
    public function rollbackDocument($documentName)
    {
        $this->getAdapter()->rollbackDocument($this->addDocumentPrefix($documentName));
    }

    /**
     * @param string $documentName
     * @return void
     */
    public function deleteDocumentBackup($documentName)
    {
        $this->getAdapter()->deleteBackup($this->addDocumentPrefix($documentName));
    }

    /**
     * @param string $documentName
     * @param \Migration\ResourceModel\Record\Collection $records
     * @return void
     */
    public function updateChangedRecords($documentName, $records)
    {
        $documentName = $this->addDocumentPrefix($documentName);
        $data = [];
        /** @var \Migration\ResourceModel\Record $row */
        foreach ($records as $row) {
            $data[] = $row->getData();
        }
        if (!empty($data)) {
            $this->getAdapter()->updateChangedRecords($this->addDocumentPrefix($documentName), $data);
        }
    }
}
