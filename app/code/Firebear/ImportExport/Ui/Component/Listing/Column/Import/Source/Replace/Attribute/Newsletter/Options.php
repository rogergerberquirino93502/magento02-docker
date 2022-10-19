<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\Newsletter;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 *
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $newsletterOptions = [
        'subscriber_id' => 'Subscriber ID',
        'subscriber_email' => 'Subscriber Email',
        'firstname' => 'Firstname',
        'lastname' => 'Lastname',
        'subscriber_status' => 'Subscriber Status',
        'subscriber_confirm_code' => 'Subscriber Confirm Code'
    ];

    /**
     * @return array
     */
    private function getNewsletterOptions()
    {
        foreach ($this->newsletterOptions as $value => $label) {
            $label = sprintf('%s (%s)', $value, $label);
            $this->options[] = ['label' => (string)__($label), 'value' => $value];
        }
        return $this->options;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return $this->getNewsletterOptions();
    }
}
