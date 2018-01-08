<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (!defined('IN_COMMON'))
{
	exit();
}

class Pagination
{
	protected $totalPages, $startRow , $currentPage;

    /**
     * @param $rowsPerPage
     * @param $numRows
     * @param int $currentPage
     */
	public function __construct($rowsPerPage, $numRows, $currentPage = 1)
	{ 
		// Calculate the total number of pages 
		$this->setTotalPages(ceil($numRows/$rowsPerPage));

		// Check that a valid page has been provided 
		$this->currentPage = $currentPage < 1 ? 1 :  ($currentPage > $this->totalPages ? $this->totalPages : $currentPage); 

		// Calculate the row to start the select with 
		$this->startRow = ($this->currentPage - 1) * $rowsPerPage; 
	}

    /**
     * Get the total pages
     * @return float
     */
    public function getTotalPages()
	{
		return $this->totalPages;
	}

    /**
     * Set the total pages
     * @param int $totalPages
     * @return int
     */
    public function setTotalPages($totalPages = 0)
    {
        return $this->totalPages = $totalPages;
    }


    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param int $currentPage
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
    }


    /**
     * @return int
     */
    public function getStartRow()
	{
		return $this->startRow;
	}


    /**
     * @param int $startRow
     */
    public function setStartRow($startRow)
    {
        $this->startRow = $startRow;
    }


    /**
     * @param $link
     * @param string $link_plus
     * @return string
     */
    public function print_nums($link, $link_plus = '')
	{
		global $lang, $config;

		//if no page
		if($this->totalPages <= 1)
		{
			return '';
		}

		$link_plus .= $link_plus != '' ? ' ' : '';

		$re = '<nav aria-label="Page navigation example">';
		$re = '<ul id="pagination" class="pagination">';

		// Add a previous page link
		if ($this->totalPages > 1 && $this->currentPage > 1)
		{
		    $re .= '<li class="page-item">';
			$re .= $config['mod_writer'] && !defined('IN_ADMIN')
                ? '<a class="paginate phover page-link" href="' . $link . '-' . ($this->currentPage-1) . '.html"' . $link_plus . '><span>' . $lang['PREV'] . '</span></a>'
                : '<a class="paginate phover page-link" href="' . $link . '&amp;page=' . ($this->currentPage-1) . '"' . $link_plus . '><span>' . $lang['PREV'] . '</span></a>';
            $re .= '</li>';
		}

		if ($this->currentPage > 3)
		{
            $re .= '<li class="page-item">';
			$re .= $config['mod_writer'] && !defined('IN_ADMIN')
                ? '<a class="paginate page-link" href="' . $link . '-1.html"' . $link_plus . '><span>1</span></a>' . ($this->currentPage > 5 ? '<a class="paginate dots"><span>...</span></a>' : '')
                : '<a class="paginate page-link" href="' . $link . '&amp;page=1"' . $link_plus . '><span>1</span></a>' . ($this->currentPage > 5 ? '<a class="paginate dots"><span>...</span></a>' : '');
            $re .= '</li>';
		}

		for ($current = ($this->currentPage == 5) ? $this->currentPage - 3 : $this->currentPage - 2, $stop = ($this->currentPage + 4 == $this->totalPages) ? $this->currentPage + 4 : $this->currentPage + 3; $current < $stop; ++$current)
		{
			if ($current < 1 || $current > $this->totalPages)
			{
				continue;
			}
			else if ($current != $this->currentPage)
			{

                $re .= '<li class="page-item">';
				$re .= $config['mod_writer'] && !defined('IN_ADMIN')
                    ? '<a class="paginate page-link" href="' . $link . '-' . $current . '.html"' . $link_plus . '><span>' . $current . '</span></a>'
                    : '<a class="paginate page-link" href="' . $link . '&amp;page=' . $current . '"' . $link_plus . '><span>' . $current . '</span></a>';
                $re .= '</li>';
			}
			else
			{
                $re .= '<li class="page-item">';
				$re .= '<a class="paginate page-link current"><span>' . $current . '</span></a>';
                $re .= '</li>';
			}
		}

		if ($this->currentPage <= ($this->totalPages-3))
		{
			if ($this->currentPage != ($this->totalPages-3) && $this->currentPage != ($this->totalPages-4))
			{
				$re .= '<li class="page-item"><a class="paginate page-link dots"><span>...</span></a></li>';
			}

            $re .= '<li class="page-item">';
			$re .= $config['mod_writer'] && !defined('IN_ADMIN')
                ? '<a class="paginate page-link" href="' . $link . '-' . $this->totalPages . '.html"' . $link_plus . '><span>' . $this->totalPages . '</span></a>'
                : '<a class="paginate page-link" href="' . $link . '&amp;page=' . $this->totalPages . '"' . $link_plus . '><span>' . $this->totalPages . '</span></a>';
            $re .= '</li>';
		}

		// Add a next page link
		if ($this->totalPages > 1 && $this->currentPage < $this->totalPages)
		{
            $re .= '<li class="page-item">';
			$re .= $config['mod_writer'] && !defined('IN_ADMIN')
                ? '<a class="paginate page-link phover" href="' . $link . '-' . ($this->currentPage+1) . '.html"' . $link_plus . '><span>' . $lang['NEXT'] . '</span></a>'
                : '<a class="paginate phover page-link" href="' . $link . '&amp;page=' . ($this->currentPage+1) . '"' . $link_plus . '><span>' . $lang['NEXT'] . '</span></a>';
            $re .= '</li>';
		}

		$re .= '</ul>';
		$re .= '</nav>';

		return $re;
	}
}

