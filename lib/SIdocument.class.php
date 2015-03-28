<?php

require 'tcpdf_min/tcpdf.php';
require 'PDFMerger/PDFMerger.php';

class SIdocument {
    
    public function createsignpage ($text,$signatureimagepath,$outputpath) {
    	// create new PDF document
    	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    	
    	// set default monospaced font
    	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    	
    	// set margins
    	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    	$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    	$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    	
    	// set auto page breaks
    	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    	
    	// set image scale factor
    	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    	
    	// set some language-dependent strings (optional)
    	if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    	    require_once(dirname(__FILE__).'/lang/eng.php');
    	    $pdf->setLanguageArray($l);
    	}
    	
    	// ---------------------------------------------------------
    	
    	// set font
    	$pdf->SetFont('helvetica', '', 12);
    	
    	// add a page
    	$pdf->AddPage();
    	
    	// print a line of text
    	$pdf->writeHTML($text, true, 0, true, 0);
    	
    	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    	// *** set signature appearance ***
    	
    	// create content for signature (image and/rm or text)
    	$pdf->Image($signatureimagepath, 150, '', 30, '', 'JPG');
    	
        //$outputpath = dirname(__FILE__).'/'.$outputpath;
        
    	$pdf->Output($outputpath, 'F');
    	
    	if (file_exists($outputpath)) {
    		return 1;
    	} else return 0;
    }
    
    public function createsignedpdf($sourcepdfpath,$signedpdfpath,$outputpath) {
    	$pdf = new PDFMerger;
    	
    	$pdf->addPDF($sourcepdfpath, 'all')
    		->addPDF($signedpdfpath, '1')
    		->merge('file', $outputpath);
    		
    	if (file_exists($outputpath)) {
    		return 1;
    	} else return 0;
    }
}