<?php
/**
 * Author: Xavier Au
 * Date: 12/6/2017
 * Time: 2:44 PM
 */

namespace Anacreation\PageComposer\Contracts;


interface ParserInterface
{

    public static function parseComponents(string $file_location): array;

    public static function parseVariables(string $file_location): array;
}