<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Email;

use Magento\Framework\Exception\MailException;

/**
 * Class AddressConverter
 */
class AddressConverter
{
    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * AddressConverter constructor
     *
     * @param AddressFactory $addressFactory
     */
    public function __construct(
        AddressFactory $addressFactory
    ) {
        $this->addressFactory = $addressFactory;
    }

    /**
     * Creates MailAddress from string values
     *
     * @param string $email
     * @param string|null $name
     *
     * @return Address
     */
    public function convert(string $email, $name = null): Address
    {
        return $this->addressFactory->create(
            [
                'name' => $name,
                'email' => $email
            ]
        );
    }

    /**
     * Converts array to list of MailAddresses
     *
     * @param array $addresses
     *
     * @return Address[]
     * @throws MailException
     */
    public function convertMany(array $addresses): array
    {
        $addressList = [];
        foreach ($addresses as $key => $value) {

            if (is_int($key) || is_numeric($key)) {
                $addressList[] = $this->convert($value);
                continue;
            }

            if (!is_string($key)) {
                $message = sprintf(
                    'Invalid key type in provided addresses array ("%s")',
                    (is_object($key) ? get_class($key) : var_export($key, 1))
                );
                throw new MailException(__($message));
            }
            $addressList[] = $this->convert($key, $value);
        }

        return $addressList;
    }
}
