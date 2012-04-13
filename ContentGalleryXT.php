<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Oliver Lohoff 2012
 * @author     Oliver Lohoff <http://www.contao4you.de>
 * @package    Frontend
 * @license    LGPL
 * @filesource
 */


/**
 * Class ContentGallery
 *
 * Front end content element "gallery".
 * @copyright  Oliver Lohoff 2012
 * @author     Oliver Lohoff <http://www.contao4you.de>
 * @package    Contao
 */
class ContentGalleryXT extends ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_gallery_xt';


	/**
	 * Return if there are no files
	 * @return string
	 */
	public function generate()
	{
		$this->multiSRC = deserialize($this->multiSRC);

		// Use the home directory of the current user as file source
		if ($this->useHomeDir && FE_USER_LOGGED_IN)
		{
			$this->import('FrontendUser', 'User');
			
			if ($this->User->assignDir && is_dir(TL_ROOT . '/' . $this->User->homeDir))
			{
				$this->multiSRC = array($this->User->homeDir);
			}
		}

		if (!is_array($this->multiSRC) || count($this->multiSRC) < 1)
		{
			return '';
		}

		return parent::generate();
	}

    protected function getPageLayout($intId)
    {
        $objLayout = $this->Database->prepare("SELECT l.*, t.templates FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id WHERE l.id=? OR l.fallback=1 ORDER BY l.id=? DESC")
                                    ->limit(1)
                                    ->execute($intId, $intId);

        // Die if there is no layout at all
        if ($objLayout->numRows < 1)
        {
            header('HTTP/1.1 501 Not Implemented');
            $this->log('Could not find layout ID "' . $intId . '"', 'PageRegular getPageLayout()', TL_ERROR);
            die('No layout specified');
        }

        return $objLayout;
    }

    public function generateAjax()
    {
        // es wird das Output-Format benötigt
        $objDB = $this->Database->prepare('SELECT * FROM tl_page WHERE id = (SELECT pid FROM tl_article WHERE id=?)')->execute($this->pid);
        global $objPage;
        $objPage = (object) $objDB->fetchAssoc();
        $objLayout = $this->getPageLayout($objPage->layout);
        list($strFormat, $strVariant) = explode('_', $objLayout->doctype);
        $objPage->outputFormat = $strFormat;
        $objPage->outputVariant = $strVariant;

        define('TL_FILES_URL', ($objPage->staticFiles != '' && !$GLOBALS['TL_CONFIG']['debugMode']) ? $objPage->staticFiles . TL_PATH . '/' : '');
    	$ajaxTemplate = new FrontendTemplate("ce_gallery_xt_ajax");
        $images = array();
      		$auxDate = array();

      		// Get all images
            $this->multiSRC = deserialize($this->multiSRC);
      		foreach ($this->multiSRC as $file)
      		{
      			if (isset($images[$file]) || !file_exists(TL_ROOT . '/' . $file))
      			{
      				continue;
      			}

      			// Single files
      			if (is_file(TL_ROOT . '/' . $file))
      			{
      				$objFile = new File($file);
      				$this->parseMetaFile(dirname($file), true);
      				$arrMeta = $this->arrMeta[$objFile->basename];

      				if ($arrMeta[0] == '')
      				{
      					$arrMeta[0] = str_replace('_', ' ', preg_replace('/^[0-9]+_/', '', $objFile->filename));
      				}

      				if ($objFile->isGdImage)
      				{
      					$images[$file] = array
      					(
      						'name' => $objFile->basename,
      						'singleSRC' => $file,
      						'alt' => $arrMeta[0],
      						'imageUrl' => $arrMeta[1],
      						'caption' => $arrMeta[2]
      					);

      					$auxDate[] = $objFile->mtime;
      				}

      				continue;
      			}

      			$subfiles = scan(TL_ROOT . '/' . $file);
      			$this->parseMetaFile($file);

      			// Folders
      			foreach ($subfiles as $subfile)
      			{
      				if (is_dir(TL_ROOT . '/' . $file . '/' . $subfile))
      				{
      					continue;
      				}

      				$objFile = new File($file . '/' . $subfile);

      				if ($objFile->isGdImage)
      				{
      					$arrMeta = $this->arrMeta[$subfile];

      					if ($arrMeta[0] == '')
      					{
      						$arrMeta[0] = str_replace('_', ' ', preg_replace('/^[0-9]+_/', '', $objFile->filename));
      					}

      					$images[$file . '/' . $subfile] = array
      					(
      						'name' => $objFile->basename,
      						'singleSRC' => $file . '/' . $subfile,
      						'alt' => $arrMeta[0],
      						'imageUrl' => $arrMeta[1],
      						'caption' => $arrMeta[2]
      					);

      					$auxDate[] = $objFile->mtime;
      				}
      			}
      		}

      		// Sort array
      		switch ($this->sortBy)
      		{
      			default:
      			case 'name_asc':
      				uksort($images, 'basename_natcasecmp');
      				break;

      			case 'name_desc':
      				uksort($images, 'basename_natcasercmp');
      				break;

      			case 'date_asc':
      				array_multisort($images, SORT_NUMERIC, $auxDate, SORT_ASC);
      				break;

      			case 'date_desc':
      				array_multisort($images, SORT_NUMERIC, $auxDate, SORT_DESC);
      				break;

      			case 'meta':
      				$arrImages = array();
      				foreach ($this->arrAux as $k)
      				{
      					if (strlen($k))
      					{
      						$arrImages[] = $images[$k];
      					}
      				}
      				$images = $arrImages;
      				break;

      			case 'random':
      				shuffle($images);
      				break;
      		}

      		$images = array_values($images);
      		$total = count($images);
      		$limit = $total;
      		$offset = 0;

      		// Pagination
      		if ($this->perPage > 0)
      		{
      			$page = $this->Input->get('page') ? $this->Input->get('page') : 1;
      			$offset = ($page - 1) * $this->perPage;
      			$limit = min($this->perPage + $offset, $total);

      			$objPagination = new Pagination($total, $this->perPage);
      			$ajaxTemplate->pagination = $objPagination->generate("\n  ");
      		}

      		$rowcount = 0;
      		$colwidth = floor(100/$this->perRow);
      		$intMaxWidth = (TL_MODE == 'BE') ? floor((640 / $this->perRow)) : floor(($GLOBALS['TL_CONFIG']['maxImageWidth'] / $this->perRow));
            $strLightboxId = 'lightbox[lb' . $this->id . ']';
      		$body = array();

              // *************************
              // von Olli eingefügt
              // *************************
              $arrImg = array();
              $arrImgPre = array();
              $arrImgPost = array();

              for ($i=0; $i<$total; $i++)
              {
                  $arrImg['href'] = $this->urlEncode($images[$i]['singleSRC']);
                  if (!empty($GLOBALS['TL_CONFIG']['latestVersion']) && version_compare(VERSION . '.' . BUILD, 2.11, '<') || $objPage->outputFormat == 'xhtml')
                  {
                      $arrImg['attributes'] = 'rel="lightbox[lb' . $this->id . ']"';
                  }
                  else
                  {
                      $arrImg['attributes'] = 'data-lightbox="lb' . $this->id . '"';
                  }
                  if ($i<$offset)
                  {
                      $arrImgPre[] = $arrImg;
                  }
                  elseif ($i>=$limit)
                  {
                      $arrImgPost[] = $arrImg;
                  }
              }

              // **************************
              // **************************

      		// Rows
      		for ($i=$offset; $i<$limit; $i=($i+$this->perRow))
      		{
      			$class_tr = '';

      			if ($rowcount == 0)
      			{
      				$class_tr .= ' row_first';
      			}

      			if (($i + $this->perRow) >= $limit)
      			{
      				$class_tr .= ' row_last';
      			}

      			$class_eo = (($rowcount % 2) == 0) ? ' even' : ' odd';

      			// Columns
      			for ($j=0; $j<$this->perRow; $j++)
      			{
      				$class_td = '';

      				if ($j == 0)
      				{
      					$class_td = ' col_first';
      				}

      				if ($j == ($this->perRow - 1))
      				{
      					$class_td = ' col_last';
      				}

      				$objCell = new stdClass();
      				$key = 'row_' . $rowcount . $class_tr . $class_eo;

      				// Empty cell
      				if (!is_array($images[($i+$j)]) || ($j+$i) >= $limit)
      				{
      					$objCell->class = 'col_'.$j . $class_td;
      					$body[$key][$j] = $objCell;

      					continue;
      				}

      				// Add size and margin
      				$images[($i+$j)]['size'] = $this->size;
      				$images[($i+$j)]['imagemargin'] = $this->imagemargin;
      				$images[($i+$j)]['fullsize'] = $this->fullsize;

      				$this->addImageToTemplate($objCell, $images[($i+$j)], $intMaxWidth, $strLightboxId);

      				// Add column width and class
      				$objCell->colWidth = $colwidth . '%';
      				$objCell->class = 'col_'.$j . $class_td;

      				$body[$key][$j] = $objCell;
      			}

      			++$rowcount;
      		}

      		$strTemplate = 'gallery_default_xt';

      		// Use a custom template
      		if (TL_MODE == 'FE' && $this->galleryTpl != '')
      		{
      			//$strTemplate = $this->galleryTpl;
      		}

      		$objTemplate = new FrontendTemplate($strTemplate);
      		$objTemplate->setData($this->arrData);

      		$objTemplate->body = $body;
      		$objTemplate->headline = $this->headline; // see #1603

              // *************************
              // von Olli eingefügt
              // *************************
              $objTemplate->ImagesPre = $arrImgPre;
              $objTemplate->ImagesPost = $arrImgPost;
              // *************************
              // *************************


      		$ajaxTemplate->images = $objTemplate->parse();
            return $ajaxTemplate->parse();
    }


	/**
	 * Generate content element
	 */
	protected function compile()
	{
		$images = array();
		$auxDate = array();
        global $objPage;

		// Get all images
		foreach ($this->multiSRC as $file)
		{
			if (isset($images[$file]) || !file_exists(TL_ROOT . '/' . $file))
			{
				continue;
			}

			// Single files
			if (is_file(TL_ROOT . '/' . $file))
			{
				$objFile = new File($file);
				$this->parseMetaFile(dirname($file), true);
				$arrMeta = $this->arrMeta[$objFile->basename];

				if ($arrMeta[0] == '')
				{
					$arrMeta[0] = str_replace('_', ' ', preg_replace('/^[0-9]+_/', '', $objFile->filename));
				}

				if ($objFile->isGdImage)
				{
					$images[$file] = array
					(
						'name' => $objFile->basename,
						'singleSRC' => $file,
						'alt' => $arrMeta[0],
						'imageUrl' => $arrMeta[1],
						'caption' => $arrMeta[2]
					);

					$auxDate[] = $objFile->mtime;
				}

				continue;
			}

			$subfiles = scan(TL_ROOT . '/' . $file);
			$this->parseMetaFile($file);

			// Folders
			foreach ($subfiles as $subfile)
			{
				if (is_dir(TL_ROOT . '/' . $file . '/' . $subfile))
				{
					continue;
				}

				$objFile = new File($file . '/' . $subfile);

				if ($objFile->isGdImage)
				{
					$arrMeta = $this->arrMeta[$subfile];

					if ($arrMeta[0] == '')
					{
						$arrMeta[0] = str_replace('_', ' ', preg_replace('/^[0-9]+_/', '', $objFile->filename));
					}

					$images[$file . '/' . $subfile] = array
					(
						'name' => $objFile->basename,
						'singleSRC' => $file . '/' . $subfile,
						'alt' => $arrMeta[0],
						'imageUrl' => $arrMeta[1],
						'caption' => $arrMeta[2]
					);

					$auxDate[] = $objFile->mtime;
				}
			}
		}

		// Sort array
		switch ($this->sortBy)
		{
			default:
			case 'name_asc':
				uksort($images, 'basename_natcasecmp');
				break;

			case 'name_desc':
				uksort($images, 'basename_natcasercmp');
				break;

			case 'date_asc':
				array_multisort($images, SORT_NUMERIC, $auxDate, SORT_ASC);
				break;

			case 'date_desc':
				array_multisort($images, SORT_NUMERIC, $auxDate, SORT_DESC);
				break;

			case 'meta':
				$arrImages = array();
				foreach ($this->arrAux as $k)
				{
					if (strlen($k))
					{
						$arrImages[] = $images[$k];
					}
				}
				$images = $arrImages;
				break;

			case 'random':
				shuffle($images);
				break;
		}

		$images = array_values($images);
		$total = count($images);
		$limit = $total;
		$offset = 0;

		// Pagination
		if ($this->perPage > 0)
		{
			$page = $this->Input->get('page') ? $this->Input->get('page') : 1;
			$offset = ($page - 1) * $this->perPage;
			$limit = min($this->perPage + $offset, $total);

			$objPagination = new Pagination($total, $this->perPage);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		$rowcount = 0;
		$colwidth = floor(100/$this->perRow);
		$intMaxWidth = (TL_MODE == 'BE') ? floor((640 / $this->perRow)) : floor(($GLOBALS['TL_CONFIG']['maxImageWidth'] / $this->perRow));
        $strLightboxId = 'lightbox[lb' . $this->id . ']';
		$body = array();

        // *************************
        // von Olli eingefügt
        // *************************
        $arrImg = array();
        $arrImgPre = array();
        $arrImgPost = array();

        for ($i=0; $i<$total; $i++)
        {
            $arrImg['href'] = TL_FILES_URL . $this->urlEncode($images[$i]['singleSRC']);
            if (!empty($GLOBALS['TL_CONFIG']['latestVersion']) && version_compare(VERSION . '.' . BUILD, 2.11, '<') || $objPage->outputFormat == 'xhtml')
            {
                $arrImg['attributes'] = 'rel="lightbox[lb' . $this->id . ']"';
            }
            else
            {
                $arrImg['attributes'] = 'data-lightbox="lb' . $this->id . '"';
            }
            if ($i<$offset)
            {
                $arrImgPre[] = $arrImg;
            }
            elseif ($i>=$limit)
            {
                $arrImgPost[] = $arrImg;
            }
        }

        // **************************
        // **************************

		// Rows
		for ($i=$offset; $i<$limit; $i=($i+$this->perRow))
		{
			$class_tr = '';

			if ($rowcount == 0)
			{
				$class_tr .= ' row_first';
			}

			if (($i + $this->perRow) >= $limit)
			{
				$class_tr .= ' row_last';
			}

			$class_eo = (($rowcount % 2) == 0) ? ' even' : ' odd';

			// Columns
			for ($j=0; $j<$this->perRow; $j++)
			{
				$class_td = '';

				if ($j == 0)
				{
					$class_td = ' col_first';
				}

				if ($j == ($this->perRow - 1))
				{
					$class_td = ' col_last';
				}

				$objCell = new stdClass();
				$key = 'row_' . $rowcount . $class_tr . $class_eo;

				// Empty cell
				if (!is_array($images[($i+$j)]) || ($j+$i) >= $limit)
				{
					$objCell->class = 'col_'.$j . $class_td;
					$body[$key][$j] = $objCell;

					continue;
				}

				// Add size and margin
				$images[($i+$j)]['size'] = $this->size;
				$images[($i+$j)]['imagemargin'] = $this->imagemargin;
				$images[($i+$j)]['fullsize'] = $this->fullsize;

				$this->addImageToTemplate($objCell, $images[($i+$j)], $intMaxWidth, $strLightboxId);

				// Add column width and class
				$objCell->colWidth = $colwidth . '%';
				$objCell->class = 'col_'.$j . $class_td;

				$body[$key][$j] = $objCell;
			}

			++$rowcount;
		}

		$strTemplate = 'gallery_default_xt';

		// Use a custom template
		if (TL_MODE == 'FE' && $this->galleryTpl != '')
		{
			//$strTemplate = $this->galleryTpl;
		}

		$objTemplate = new FrontendTemplate($strTemplate);
		$objTemplate->setData($this->arrData);

		$objTemplate->body = $body;
		$objTemplate->headline = $this->headline; // see #1603

        // *************************
        // von Olli eingefügt
        // *************************
        $objTemplate->ImagesPre = $arrImgPre;
        $objTemplate->ImagesPost = $arrImgPost;
        // *************************
        // *************************


		$this->Template->images = $objTemplate->parse();
	}
}

?>