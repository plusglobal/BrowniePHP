<?php
/**
* Based on http://bakery.cakephp.org/articles/makio/2010/09/28/generate-xml-excel
*/

class XmlExcelHelper extends AppHelper {
    public $data;
    public $header;
    public $rows;
    public $title;
    public $sheetName;
    public $columnsWidth;
    public $columns;
    public $xls;

    public function generate($data, $title, $columnWidth = null) {
        $this->data = $data;
        $this->columnsWidth = $columnWidth;
        $this->sheetName = $title;
        $this->title = '<Cell ss:StyleID="s16"><Data ss:Type="String">'.$title.'</Data></Cell>';
        $this->setHeader();
        if (isset($this->columnsWidth)) {
            $this->setColumWidth();
        }
        $this->setRows();
        $this->getXML();
        //header('Content-type: application/xml');
		//header('Content-Disposition: attachment; filename="'.$this->sheetName.' - '.date("d M Y").'.xls"');
        echo $this->xls;
    }

    public function setHeader() {
        foreach ($this->data as $row) {
            foreach ($row as $model){
                foreach ($model as $field => $value)
                    $this->header.='<Cell ss:StyleID="s17"><Data ss:Type="String">'.Inflector::humanize($field).'</Data></Cell>';
            }
            break;
        }
    }

    public function setColumWidth(){
        foreach ($this->columnsWidth as $width){
            $this->columns.='<Column ss:Width="'.$width.'"/>';
        }
        $this->columns;
    }

    public function setRows(){
        foreach ($this->data as $row) {
            $this->rows .='<Row>' . "\n";
            foreach ($row as $model) {
                foreach ($model as $field) {
                    $this->rows .= '<Cell><Data ss:Type="String">' . $field . '</Data></Cell>' . "\n";
                }
            }
            $this->rows .= '</Row>' . "\n";
        }
    }

    public function getXML(){
	    $this->xls = '<?xml version="1.0"?>
		<?mso-application progid="Excel.Sheet"?>
		<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office"
		xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
		xmlns:html="http://www.w3.org/TR/REC-html40">
			<Styles>
				<Style ss:ID="s17">
					<Font ss:FontName="Arial" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
					<Interior ss:Color="#BFBFBF" ss:Pattern="Solid"/>
				</Style>
			</Styles>
			<Worksheet ss:Name="' . $this->sheetName . '">
				<Table>
					' . $this->columns . '
					<Row ss:Index="1">' . $this->header . '</Row>
					' . $this->rows . '
				</Table>
			</Worksheet>
		</Workbook>';
    }
}

