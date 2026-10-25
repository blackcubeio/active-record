<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support;

use Blackcube\MagicCompose\Attributes\MagicExtend;
use Blackcube\MagicCompose\Attributes\MagicGetter;
use Blackcube\MagicCompose\Attributes\MagicSetter;
use Blackcube\MagicCompose\Attributes\Priority;
use Blackcube\MagicCompose\Exceptions\MagicNotHandledException;

/**
 * Mock trait with MagicExtend handlers for testing MagicComposeActiveRecordTrait.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait TestMockTraitA
{
    private ?string $extraA = null;
    private array $traitALog = [];


    #[MagicGetter(Priority::HIGH)]
    protected function mockTraitAGet(string $name): mixed
    {
        if ($name === 'extraA') {
            return $this->extraA;
        }
        if ($name === 'traitALog') {
            return $this->traitALog;
        }
        throw new MagicNotHandledException();
    }

    #[MagicSetter(Priority::HIGH)]
    protected function mockTraitASet(string $name, mixed $value): void
    {
        if ($name === 'extraA') {
            $this->extraA = $value;
            return;
        }
        throw new MagicNotHandledException();
    }


    #[MagicExtend('propertyValuesInternal', Priority::HIGH)]
    protected function mockTraitAPropertyValues(): array
    {
        $this->traitALog[] = 'propertyValues:before';
        $values = $this->next();
        $this->traitALog[] = 'propertyValues:after';
        $values['extraA'] = $this->extraA;
        return $values;
    }

    #[MagicExtend('populateRecord', Priority::HIGH)]
    protected function mockTraitAPopulateRecord(array|object $row): static
    {
        $this->traitALog[] = 'populateRecord:before';
        $result = $this->next($row);
        $this->traitALog[] = 'populateRecord:after';
        return $result;
    }

    #[MagicExtend('populateProperty', Priority::HIGH)]
    protected function mockTraitAPopulateProperty(string $name, mixed $value): void
    {
        $this->traitALog[] = 'populateProperty:' . $name;
        if ($name === 'extraA') {
            $this->extraA = $value;
            return;
        }
        $this->next($name, $value);
    }

    #[MagicExtend('refreshInternal', Priority::HIGH)]
    protected function mockTraitARefreshInternal(array|\Yiisoft\ActiveRecord\ActiveRecordInterface|null $record): bool
    {
        $this->traitALog[] = 'refreshInternal:before';
        $result = $this->next($record);
        $this->traitALog[] = 'refreshInternal:after';
        return $result;
    }

    #[MagicExtend('deleteInternal', Priority::HIGH)]
    protected function mockTraitADeleteInternal(): int
    {
        $this->traitALog[] = 'deleteInternal:before';
        $result = $this->next();
        $this->traitALog[] = 'deleteInternal:after';
        return $result;
    }

    public function clearTraitALog(): void
    {
        $this->traitALog = [];
    }
}
