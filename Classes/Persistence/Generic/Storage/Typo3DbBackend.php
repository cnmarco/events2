<?php
namespace JWeiland\Events2\Persistence\Generic\Storage;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Overwritten storage with a little implementation of GROUP BY
 */
class Typo3DbBackend extends \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend
{
    /**
     * Creates the parameters for the query methods of the database methods in the TYPO3 core, from an array
     * that came from a parsed query.
     *
     * @param array $statementParts
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function createQueryCommandParametersFromStatementParts(array $statementParts)
    {
        if (isset($statementParts['offset']) && !isset($statementParts['limit'])) {
            throw new \InvalidArgumentException(
                'Trying to make query with offset and no limit, the offset would become a limit. You have to set a limit to use offset. To retrieve all rows from a certain offset up to the end of the result set, you can use some large number for the limit.',
                1465223252
            );
        }
        return [
            'selectFields' => implode(' ', $statementParts['keywords']) . ' ' . implode(',', $statementParts['fields']),
            'fromTable'    => implode(' ', $statementParts['tables']) . ' ' . implode(' ', $statementParts['unions']),
            'whereClause'  => (!empty($statementParts['where']) ? implode('', $statementParts['where']) : '1=1')
                . (!empty($statementParts['additionalWhereClause'])
                    ? ' AND ' . implode(' AND ', $statementParts['additionalWhereClause'])
                    : ''
            ),
            'groupBy'      => (!empty($statementParts['groupings']) ? implode(', ', $statementParts['groupings']) : ''),
            'orderBy'      => (!empty($statementParts['orderings']) ? implode(', ', $statementParts['orderings']) : ''),
            'limit'        => ($statementParts['offset'] ? $statementParts['offset'] . ', ' : '')
                . ($statementParts['limit'] ? $statementParts['limit'] : '')
        ];
    }

    /**
     * Fetches the rows directly from the database, not using prepared statement
     *
     * @param array $statementParts
     * @return array the result
     */
    protected function getRowsFromDatabase(array $statementParts)
    {
        $queryCommandParameters = $this->createQueryCommandParametersFromStatementParts($statementParts);
        $rows = $this->databaseHandle->exec_SELECTgetRows(
            $queryCommandParameters['selectFields'],
            $queryCommandParameters['fromTable'],
            $queryCommandParameters['whereClause'],
            $queryCommandParameters['groupBy'],
            $queryCommandParameters['orderBy'],
            $queryCommandParameters['limit']
        );
        $this->checkSqlErrors();

        return $rows;
    }

    /**
     * Fetches the rows from the database, using prepared statement
     *
     * @param array $statementParts
     * @param array $parameters
     * @return array the result
     */
    protected function getRowsFromPreparedDatabase(array $statementParts, array $parameters)
    {
        $queryCommandParameters = $this->createQueryCommandParametersFromStatementParts($statementParts);
        $preparedStatement = $this->databaseHandle->prepare_SELECTquery(
            $queryCommandParameters['selectFields'],
            $queryCommandParameters['fromTable'],
            $queryCommandParameters['whereClause'],
            $queryCommandParameters['groupBy'],
            $queryCommandParameters['orderBy'],
            $queryCommandParameters['limit']
        );

        $preparedStatement->execute($parameters);
        $rows = $preparedStatement->fetchAll();

        $preparedStatement->free();
        return $rows;
    }
}