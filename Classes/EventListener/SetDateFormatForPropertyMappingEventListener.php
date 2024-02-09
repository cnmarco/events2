<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\Property\TypeConverter\DateTimeImmutableConverter;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;

/**
 * Remove videoLink if empty.
 * Add special validation for VideoLink id exists.
 * I can't add this validation to LinkModel, as such a validation would be also valid for organizer link.
 */
class SetDateFormatForPropertyMappingEventListener extends AbstractControllerEventListener
{
    protected string $defaultDateFormat = 'd.m.Y';

    protected array $allowedControllerActions = [
        'Management' => [
            'create',
            'update'
        ]
    ];

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if ($this->isValidRequest($controllerActionEvent)) {
            $eventMappingConfiguration = $controllerActionEvent->getArguments()
                ->getArgument('event')
                ->getPropertyMappingConfiguration();

            $this->setDatePropertyFormat('eventBegin', $eventMappingConfiguration);
            $this->setDatePropertyFormat('eventEnd', $eventMappingConfiguration);
        }
    }

    protected function setDatePropertyFormat(
        string $property,
        PropertyMappingConfigurationInterface $pmc
    ): void {
        $pmc
            ->forProperty($property)
            ->setTypeConverterOption(
                DateTimeImmutableConverter::class,
                DateTimeImmutableConverter::CONFIGURATION_DATE_FORMAT,
                $this->defaultDateFormat
            );
    }
}
