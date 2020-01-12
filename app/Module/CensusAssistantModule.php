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

namespace Fisharebest\Webtrees\Module;

use Fisharebest\Webtrees\Census\CensusInterface;
use Fisharebest\Webtrees\Exceptions\HttpNotFoundException;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function assert;
use function response;

/**
 * Class CensusAssistantModule
 */
class CensusAssistantModule extends AbstractModule
{
    /**
     * How should this module be identified in the control panel, etc.?
     *
     * @return string
     */
    public function title(): string
    {
        /* I18N: Name of a module */
        return I18N::translate('Census assistant');
    }

    /**
     * A sentence describing what this module does.
     *
     * @return string
     */
    public function description(): string
    {
        /* I18N: Description of the “Census assistant” module */
        return I18N::translate('An alternative way to enter census transcripts and link them to individuals.');
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function postCensusHeaderAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = (array) $request->getParsedBody();

        $census = $params['census'];

        $html = $this->censusTableHeader(new $census());

        return response($html);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function postCensusIndividualAction(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $params       = (array) $request->getParsedBody();
        $individual   = Individual::getInstance($params['xref'], $tree);
        $head         = Individual::getInstance($params['head'], $tree);
        $census_class = $params['census'];
        $census       = new $census_class();

        if ($individual instanceof Individual && $head instanceof Individual) {
            $html = $this->censusTableRow($census, $individual, $head);
        } else {
            $html = $this->censusTableEmptyRow($census);
        }

        return response($html);
    }

    /**
     * @param Individual $individual
     *
     * @return string
     */
    public function createCensusAssistant(Individual $individual): string
    {
        return view('modules/census-assistant', [
            'individual' => $individual,
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Individual             $individual
     * @param string                 $fact_id
     * @param string                 $newged
     * @param bool                   $keep_chan
     *
     * @return string
     */
    public function updateCensusAssistant(ServerRequestInterface $request, Individual $individual, string $fact_id, string $newged, bool $keep_chan): string
    {
        $params = (array) $request->getParsedBody();

        $ca_title       = $params['ca_title'] ?? '';
        $ca_place       = $params['ca_place'] ?? '';
        $ca_citation    = $params['ca_citation'] ?? '';
        $ca_individuals = $params['ca_individuals'] ?? [];
        $ca_notes       = $params['ca_notes'] ?? '';
        $ca_census      = $params['ca_census'] ?? '';

        if ($ca_census !== '' && $ca_individuals !== []) {
            $census = new $ca_census();

            $note_text   = $this->createNoteText($census, $ca_title, $ca_place, $ca_citation, $ca_individuals, $ca_notes);
            $note_gedcom = '0 @@ NOTE ' . str_replace("\n", "\n1 CONT ", $note_text);
            $note        = $individual->tree()->createRecord($note_gedcom);

            $newged .= "\n2 NOTE @" . $note->xref() . '@';

            // Add the census fact to the rest of the household
            foreach (array_keys($ca_individuals) as $xref) {
                $xref = (string) $xref;
                if ($xref !== $individual->xref()) {
                    Individual::getInstance($xref, $individual->tree())
                        ->updateFact($fact_id, $newged, !$keep_chan);
                }
            }
        }

        return $newged;
    }

    /**
     * @param CensusInterface $census
     * @param string          $ca_title
     * @param string          $ca_place
     * @param string          $ca_citation
     * @param string[][]      $ca_individuals
     * @param string          $ca_notes
     *
     * @return string
     */
    private function createNoteText(CensusInterface $census, $ca_title, $ca_place, $ca_citation, $ca_individuals, $ca_notes): string
    {
        $text = $ca_title . "\n" . $ca_citation . "\n" . $ca_place . "\n\n";

        foreach ($census->columns() as $n => $column) {
            if ($n === 0) {
                $text .= "\n";
            } else {
                $text .= ' | ';
            }
            $text .= $column->abbreviation();
        }

        foreach ($census->columns() as $n => $column) {
            if ($n === 0) {
                $text .= "\n";
            } else {
                $text .= ' | ';
            }
            $text .= '-----';
        }

        foreach ($ca_individuals as $columns) {
            $text .= "\n" . implode(' | ', $columns);
        }

        return $text . "\n\n" . $ca_notes;
    }

    /**
     * Generate an HTML row of data for the census header
     * Add prefix cell (store XREF and drag/drop)
     * Add suffix cell (delete button)
     *
     * @param CensusInterface $census
     *
     * @return string
     */
    protected function censusTableHeader(CensusInterface $census): string
    {
        $html = '';
        foreach ($census->columns() as $column) {
            $html .= '<th class="wt-census-assistant-field" title="' . $column->title() . '">' . $column->abbreviation() . '</th>';
        }

        return '<tr class="wt-census-assistant-row"><th hidden></th>' . $html . '<th></th></tr>';
    }

    /**
     * Generate an HTML row of data for the census
     * Add prefix cell (store XREF and drag/drop)
     * Add suffix cell (delete button)
     *
     * @param CensusInterface $census
     *
     * @return string
     */
    public function censusTableEmptyRow(CensusInterface $census): string
    {
        return '<tr class="wt-census-assistant-row"><td hidden></td>' . str_repeat('<td class="wt-census-assistant-field p-0"><input type="text" class="form-control wt-census-assistant-form-control p-0"></td>', count($census->columns())) . '<td><a href="#" title="' . I18N::translate('Remove') . '">' . view('icons/delete') . '</a></td></tr>';
    }

    /**
     * Generate an HTML row of data for the census
     * Add prefix cell (store XREF and drag/drop)
     * Add suffix cell (delete button)
     *
     * @param CensusInterface $census
     * @param Individual      $individual
     * @param Individual      $head
     *
     * @return string
     */
    public function censusTableRow(CensusInterface $census, Individual $individual, Individual $head): string
    {
        $html = '';
        foreach ($census->columns() as $column) {
            $html .= '<td class="wt-census-assistant-field p-0"><input class="form-control wt-census-assistant-form-control p-0" type="text" value="' . $column->generate($individual, $head) . '" name="ca_individuals[' . $individual->xref() . '][]"></td>';
        }

        return '<tr class="wt-census-assistant-row"><td class="wt-census-assistant-field" hidden>' . $individual->xref() . '</td>' . $html . '<td class="wt-census-assistant-field"><a href="#" title="' . I18N::translate('Remove') . '">' . view('icons/delete') . '</a></td></tr>';
    }
}
