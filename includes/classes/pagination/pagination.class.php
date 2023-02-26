<?php

class Pagination
{
    /*
        PaginateIt - A PHP Pagination Class
        ===================================
        Author: Brady Vercher
        Version: 1.1.1
        URL: http://www.bradyvercher.com/


        Copyright And License Information
        =================================
        Copyright (c) 2005 Brady Vercher

        Permission is hereby granted, free of charge, to any person obtaining a copy of this
        software and associated documentation files (the "Software"), to deal in the Software
        without restriction, including without limitation the rights to use, copy, modify, merge,
        publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
        to whom the Software is furnished to do so, subject to the following conditions:

        The above copyright notice and this permission notice shall be included in all copies or
        substantial portions of the Software.

        THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING
        BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
        NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
        DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
        OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
    */

    public $currentPage;
    public $itemCount;
    public $itemsPerPage;
    public $linksHref;
    public $linksToDisplay;
    public $pageJumpBack;
    public $pageJumpNext;
    public $pageSeparator;
    public $queryString;
    public $queryStringVar;

    public function SetCurrentPage($reqCurrentPage)
    {
        $this->currentPage = (int) abs($reqCurrentPage);
    }

    public function SetItemCount($reqItemCount)
    {
        $this->itemCount = (int) abs($reqItemCount);
    }

    public function SetItemsPerPage($reqItemsPerPage)
    {
        $this->itemsPerPage = (int) abs($reqItemsPerPage);
    }

    public function SetLinksHref($reqLinksHref)
    {
        $this->linksHref = $reqLinksHref;
    }

    public function SetLinksFormat($reqPageJumpBack, $reqPageSeparator, $reqPageJumpNext)
    {
        $this->pageJumpBack = $reqPageJumpBack;
        $this->pageSeparator = $reqPageSeparator;
        $this->pageJumpNext = $reqPageJumpNext;
    }

    public function SetLinksToDisplay($reqLinksToDisplay)
    {
        $this->linksToDisplay = (int) abs($reqLinksToDisplay);
    }

    public function SetQueryStringVar($reqQueryStringVar)
    {
        $this->queryStringVar = $reqQueryStringVar;
    }

    public function SetQueryString($reqQueryString)
    {
        $this->queryString = $reqQueryString;
    }

    public function GetCurrentCollection($reqCollection)
    {
        if ($this->currentPage < 1) {
            $start = 0;
        } elseif ($this->currentPage > $this->GetPageCount()) {
            $start = $this->GetPageCount() * $this->itemsPerPage - $this->itemsPerPage;
        } else {
            $start = $this->currentPage * $this->itemsPerPage - $this->itemsPerPage;
        }

        return array_slice($reqCollection, $start, $this->itemsPerPage);
    }

    public function GetPageCount()
    {
        return (int) ceil($this->itemCount / $this->itemsPerPage);
    }

    public function GetPageLinks()
    {
        $strLinks = '';
        $pageCount = $this->GetPageCount();
        $queryString = $this->GetQueryString();
        $linksPad = floor($this->linksToDisplay / 2);

        if ($this->linksToDisplay == -1) {
            $this->linksToDisplay = $pageCount;
        }

        if ($pageCount == 0) {
            $strLinks = '1';
        } elseif ($this->currentPage - 1 <= $linksPad || ($pageCount - $this->linksToDisplay + 1 == 0) || $this->linksToDisplay > $pageCount) {
            $start = 1;
        } elseif ($pageCount - $this->currentPage <= $linksPad) {
            $start = $pageCount - $this->linksToDisplay + 1;
        } else {
            $start = $this->currentPage - $linksPad;
        }

        if (isset($start)) {
            if ($start > 1) {
                if (!empty($this->pageJumpBack)) {
                    $pageNum = $this->currentPage - 1;
                    if ($pageNum < 1) {
                        $pageNum = 1;
                    }

                    $strLinks .= '<a href="'.$this->linksHref.$queryString.$pageNum.'">';
                    $strLinks .= $this->pageJumpBack.'</a>'.$this->pageSeparator;
                }

                $strLinks .= '<a href="'.$this->linksHref.$queryString.'1">1&hellip;</a>'.$this->pageSeparator;
            }

            if ($start + $this->linksToDisplay > $pageCount) {
                $end = $pageCount;
            } else {
                $end = $start + $this->linksToDisplay - 1;
            }

            for ($i = $start; $i <= $end; ++$i) {
                if ($i != $this->currentPage) {
                    $strLinks .= '<a href="'.$this->linksHref.$queryString.$i.'">';
                    $strLinks .= $i.'</a>'.$this->pageSeparator;
                } else {
                    $strLinks .= '<span class="active">'.$i.'</span>'.$this->pageSeparator;
                }
            }
            $strLinks = substr($strLinks, 0, -strlen($this->pageSeparator));

            if ($this->currentPage < $pageCount) {
                if ($start + $this->linksToDisplay - 1 < $pageCount) {
                    $strLinks .= $this->pageSeparator.'<a href="'.$this->linksHref.$queryString.$pageCount.'">';
                    $strLinks .= '&hellip;'.$pageCount.'</a>'.$this->pageSeparator;
                } else {
                    $strLinks .= $this->pageSeparator;
                }
                if (!empty($this->pageJumpNext)) {
                    $pageNum = $this->currentPage + 1;
                    if ($pageNum > $pageCount) {
                        $pageNum = $pageCount;
                    }

                    $strLinks .= '<a href="'.$this->linksHref.$queryString.$pageNum.'">';
                    $strLinks .= $this->pageJumpNext.'</a>';
                }
            }
        }

        return $strLinks;
    }

    public function GetQueryString()
    {
        $pattern = ['/'.$this->queryStringVar.'=[^&]*&?/', '/&$/'];
        $replace = ['', ''];
        $queryString = preg_replace($pattern, $replace, $this->queryString);
        $queryString = str_replace('&', '&amp;', $queryString);

        if (!empty($queryString)) {
            $queryString .= '&amp;';
        }

        return '?'.$queryString.$this->queryStringVar.'=';
    }

    public function GetSqlLimit()
    {
        return ' LIMIT '.($this->currentPage * $this->itemsPerPage - $this->itemsPerPage).', '.$this->itemsPerPage;
    }

    public function Pagination($current_page = 1, $per_page = 10, $item_count = 0, $links_href = '', $query_string = '', $query_string_var = '')
    {
        if (!$current_page = (int) $current_page) {
            $current_page = 1;
        }
        if (!$per_page = (int) $per_page) {
            $per_page = 10;
        }
        if (!$item_count = (int) $item_count) {
            $item_count = 0;
        }
        if (!trim($links_href)) {
            $links_href = $_SERVER['PHP_SELF'];
        }
        if (!trim($query_string)) {
            $query_string = $_SERVER['QUERY_STRING'];
        }

        $this->SetCurrentPage($current_page);
        $this->SetItemsPerPage($per_page);
        $this->SetItemCount($item_count);
        $this->SetLinksFormat('&laquo;', ' ', '&raquo;');
        $this->SetLinksHref($links_href);
        $this->SetLinksToDisplay(5);
        if (trim($query_string_var) == '') {
            $this->SetQueryStringVar('pv');
        } else {
            $this->SetQueryStringVar($query_string_var);
        }
        $this->SetQueryString($query_string);

        if (isset($_GET[$this->queryStringVar]) && ((int) trim($_GET[$this->queryStringVar]))) {
            $this->SetCurrentPage((int) trim($_GET[$this->queryStringVar]));
        }
    }
}
