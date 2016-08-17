<?php
/**
 * 2007-2016 [PagSeguro Internet Ltda.]
 *
 * NOTICE OF LICENSE
 *
 *Licensed under the Apache License, Version 2.0 (the "License");
 *you may not use this file except in compliance with the License.
 *You may obtain a copy of the License at
 *
 *http://www.apache.org/licenses/LICENSE-2.0
 *
 *Unless required by applicable law or agreed to in writing, software
 *distributed under the License is distributed on an "AS IS" BASIS,
 *WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *See the License for the specific language governing permissions and
 *limitations under the License.
 *
 *  @author    PagSeguro Internet Ltda.
 *  @copyright 2016 PagSeguro Internet Ltda.
 *  @license   http://www.apache.org/licenses/LICENSE-2.0
 */

namespace UOL\PagSeguro\Model;

use Magento\Framework\Model\AbstractModel;


/**
 * Class Orders
 * Model for pagseguro_orders table
 * @package UOL\PagSeguro\Model
 */
class Orders extends AbstractModel
{
    /**
     * Statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    /**
     * Uol PagSeguro cache tag
     */
    const CACHE_TAG = 'uol_pagseguro';

    /**
     * @var string
     */
    protected $_cacheTag = 'uol_pagseguro';
    
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'uol_pagseguro';
    
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('UOL\PagSeguro\Model\ResourceModel\Orders');
    }
    
    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG . '_' . $this->getId()];
    }
    
    /**
     * Prepare item's statuses
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }
}
