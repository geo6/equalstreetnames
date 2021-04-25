<?php

namespace App\Wikidata;

use App\Model\Wikidata\Entity;

class Wikidata
{
    /**
     * @param Entity $entity
     * @param string[] $languages
     *
     * @return array<string,string>
     */
    public static function extractLabels(Entity $entity, array $languages): array
    {
        $labels = [];

        foreach ($languages as $language) {
            if (isset($entity->labels->{$language})) { // @phpstan-ignore-line
                $labels[$language] = $entity->labels->{$language}; // @phpstan-ignore-line
            }
        }

        return $labels;
    }

    /**
     * @param Entity $entity
     * @param string[] $languages
     *
     * @return array<string,string>
     */
    public static function extractDescriptions(Entity $entity, array $languages): array
    {
        $descriptions = [];

        foreach ($languages as $language) {
            if (isset($entity->descriptions->{$language})) { // @phpstan-ignore-line
                $descriptions[$language] = $entity->descriptions->{$language}; // @phpstan-ignore-line
            }
        }

        return $descriptions;
    }

    /**
     * @param Entity $entity
     * @param string[] $languages
     *
     * @return array<string,string>
     */
    public static function extractSitelinks(Entity $entity, array $languages): array
    {
        $sitelinks = [];

        foreach ($languages as $language) {
            if (isset($entity->sitelinks->{$language . 'wiki'})) { // @phpstan-ignore-line
                $sitelinks[$language . 'wiki'] = $entity->sitelinks->{$language . 'wiki'}; // @phpstan-ignore-line
            }
        }

        return $sitelinks;
    }

    /**
     * @param Entity $entity
     * @param string[] $languages
     *
     * @return null|array<string,string>
     */
    public static function extractNicknames(Entity $entity, array $languages): ?array
    {
        $nicknames = null;

        $claims = $entity->claims->P1449 ?? [];

        foreach ($claims as $value) {
            $language = $value->mainsnak->datavalue->value->language; // @phpstan-ignore-line

            if (in_array($language, $languages, true)) {
                $nicknames[$language] = $value->mainsnak->datavalue->value; // @phpstan-ignore-line
            }
        }

        return $nicknames;
    }

    public static function extractDateOfBirth(Entity $entity): ?string
    {
        return isset($entity->claims->P569) ? $entity->claims->P569[0]->mainsnak->datavalue->value->time ?? null : null; // @phpstan-ignore-line
    }

    public static function extractDateOfDeath(Entity $entity): ?string
    {
        return isset($entity->claims->P570) ? $entity->claims->P570[0]->mainsnak->datavalue->value->time ?? null : null; // @phpstan-ignore-line
    }

    public static function extractImage(Entity $entity): ?string
    {
        return isset($entity->claims->P18) ? $entity->claims->P18[0]->mainsnak->datavalue->value ?? null : null; // @phpstan-ignore-line
    }

    public static function extractGender(Entity $entity): ?string
    {
        $identifier = isset($entity->claims->P21) ? $entity->claims->P21[0]->mainsnak->datavalue->value->id ?? null : null; // @phpstan-ignore-line

        switch ($identifier) {
            case 'Q6581097': // male
            case 'Q15145778': // male (cis)
                return 'M';

            case 'Q6581072': // female
            case 'Q15145779': // female (cis)
                return 'F';

            case 'Q1052281': // female (trans)
                return 'FX';

            case 'Q2449503': // male (trans)
                return 'MX';

            case 'Q1097630': // intersex
                return 'X';

            case 'Q48270': // non-binary
                return 'NB';

            default:
                return null;
        }
    }

    /**
     * @return string[]
     */
    private static function extractInstances(Entity $entity): ?array
    {
        $property = $entity->claims->P31 ?? $entity->claims->P279 ?? null;

        if (is_null($property)) {
            return null;
        }

        return array_map(function ($p) {
            return $p->mainsnak->datavalue->value->id; // @phpstan-ignore-line
        }, $property);
    }

    /**
     * @param Entity $entity
     * @param array<string,bool> $instances
     */
    public static function isPerson(Entity $entity, array $instances): ?bool
    {
        $identifiers = self::extractInstances($entity);

        if (is_null($identifiers)) {
            return null;
        }

        $person = false;
        foreach ($identifiers as $id) {
            if (isset($instances[$id]) && $instances[$id] === true) {
                $person = true;
                break;
            }
        }

        return $person;
    }
}
