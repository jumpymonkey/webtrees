<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Fisharebest\Webtrees\Census;

use Fisharebest\Webtrees\TestCase;

/**
 * Test harness for the class CensusOfFrance1931
 */
class CensusOfFrance1931Test extends TestCase
{
    /**
     * Test the census place and date
     *
     * @covers \Fisharebest\Webtrees\Census\CensusOfFrance1931
     *
     * @return void
     */
    public function testPlaceAndDate(): void
    {
        $census = new CensusOfFrance1931();

        $this->assertSame('France', $census->censusPlace());
        $this->assertSame('15 JAN 1931', $census->censusDate());
    }

    /**
     * Test the census columns
     *
     * @covers \Fisharebest\Webtrees\Census\CensusOfFrance1931
     * @covers \Fisharebest\Webtrees\Census\AbstractCensusColumn
     *
     * @return void
     */
    public function testColumns(): void
    {
        $census  = new CensusOfFrance1931();
        $columns = $census->columns();

        $this->assertCount(8, $columns);
        $this->assertInstanceOf(CensusColumnSurname::class, $columns[0]);
        $this->assertInstanceOf(CensusColumnGivenNames::class, $columns[1]);
        $this->assertInstanceOf(CensusColumnBirthYear::class, $columns[2]);
        $this->assertInstanceOf(CensusColumnBirthPlace::class, $columns[3]);
        $this->assertInstanceOf(CensusColumnNationality::class, $columns[4]);
        $this->assertInstanceOf(CensusColumnRelationToHead::class, $columns[5]);
        $this->assertInstanceOf(CensusColumnOccupation::class, $columns[6]);
        $this->assertInstanceOf(CensusColumnNull::class, $columns[7]);

        $this->assertSame('Noms', $columns[0]->abbreviation());
        $this->assertSame('Prénoms', $columns[1]->abbreviation());
        $this->assertSame('Année', $columns[2]->abbreviation());
        $this->assertSame('Lieu', $columns[3]->abbreviation());
        $this->assertSame('Nationalité', $columns[4]->abbreviation());
        $this->assertSame('Situation', $columns[5]->abbreviation());
        $this->assertSame('Profession', $columns[6]->abbreviation());
        $this->assertSame('Empl', $columns[7]->abbreviation());

        $this->assertSame('Noms de famille', $columns[0]->title());
        $this->assertSame('', $columns[1]->title());
        $this->assertSame('Année de naissance', $columns[2]->title());
        $this->assertSame('Lieu de naissance', $columns[3]->title());
        $this->assertSame('', $columns[4]->title());
        $this->assertSame('Situation par rapport au chef de ménage', $columns[5]->title());
        $this->assertSame('', $columns[6]->title());
        $this->assertSame('', $columns[7]->title());
    }
}
