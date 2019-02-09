<?php
namespace Samdevbr\Bigreport\Concerns;

interface Writer
{
    /**
     * Set headings in the file
     * 
     * @param array $headings
     * @return void
     */ 
    public function setHeading(array $headings);

    /**
     * Write row with data to the file
     *  
     * @param array $row
     * @return void
     */
    public function addRow(array $row);

    /**
     * Write rows with data to the file
     * 
     * @param array $rows
     * @return void
     */
    public function addRows(array $rows);
}
