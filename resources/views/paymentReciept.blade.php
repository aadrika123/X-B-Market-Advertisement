<!DOCTYPE html>
<html moznomarginboxes mozdisallowselectionprint>
    <head>
        <meta charset="UTF-8">
        <title>Invoice</title>
        <style type="text/css" media="print">
			@page {
					margin:0 10px;
			}
			@media print{
				table {page-break-inside: avoid;}
				#buttons{
						display:none;
				}
				#invoice{
					margin-top:20px;
  				}
				td{
					padding:2px 0;
				}
                #print-btn{
                    display: none;
                }
                .text-white{
                    color:#000000;
                }
			}
		</style>
        <style>
        	.text-white{
				color:#FFFFFF;
			}
			.m-5{
				margin:5px;
				text-align:justify;
			}
			.m-10{
				margin:10px;
				text-align:justify;
			}
			.ml-20{
				margin-left:20px;
				text-align:justify;
			}
			.no-link{
				text-decoration:none;
				color:#FFFFFF;
			}
			#detail-table th,
			#detail-table td{
				padding:5px;
			}
        	.text-center{
				text-align:center;
			}
            td, th{
                padding: 3px;
            }
        </style>
    </head>
    
    <body>
        <table border="0" cellpadding="0" cellspacing="0" style="width:850px; margin:0 auto; height:900px; border:1px solid;">
            <tbody>
                <tr>
                    <td style="vertical-align:top">
                    <table border="0" cellpadding="5" cellspacing="0" style="width:850px">
                        <tbody>
                            <tr>
                                <td style="background-color:#55a003;" align="center">
                                    <h2 class="text-white m-5 text-center">cvb xvx</h2>
                                    <p class="text-white m-5 text-center">Krishna Arcade, 4th Floor, Booty More,<br>Ranchi, Jharkhand. Pin - 834009</p>
                                    <p class="text-white m-5 text-center">GSTIN : 20AAJCC0895R1Z7</p>
                                 
                                </td>
                            </tr>
                             <tr>
                                <td align="center">
                                <table border="1" cellpadding="0" cellspacing="0" style="width:98%" id="detail-table">
                                    <tbody>
                                        <tr>
                                            <th colspan="4" align="center">Tax Invoice</th>
                                        </tr>
                                        <tr>
                                            <th colspan="4" align="center">Membership Details</th>
                                        </tr>
                                        <tr>
                                            <td><b>Invoice No.</b></td>
                                            <td></td>
                                            <td><b>Date</b></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><b>Member ID</b></td>
                                            <td></td>
                                            <td><b>Member Name</b></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><b>Sponsor ID</b></td>
                                            <td></td>
                                            <td><b>Sponsor Name</b></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><b>Date of Joining</b></td>
                                            <td></td>
                                            <td><b>Joining Package</b></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><b>Place Of Supply</b></td>
                                            <td>
                                               
                                            </td>
                                            <td><b>Total BV</b></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                                </td>
                            </tr> 

                            <tr>
                                <td colspan="4">
                                    <table width="100%" border="1" cellpadding="0" cellspacing="0" style="width:98%; margin: 0 auto; text-align: center; margin-top: -5px; min-height: 600px">
                                        <tbody>
                                            <tr height="40">
                                                <th colspan="" style="text-align: center;">Purchase Details</th>
                                            </tr>
                                            <tr height="20">
                                                 <th rowspan="2">Sl.No.</th> 
                                                <th rowspan="2">Description</th>
                                                <th rowspan="2">HSN</th>
                                                <th rowspan="2">MRP</th>
                                                <th rowspan="2">Per Unit</th>
                                                <th rowspan="2">Qty</th>
                                                <th rowspan="2">Disc(%)</th>
                                                <th rowspan="2">Taxable<br>Value</th>
                                                <th colspan="2">CGST</th>
                                                <th colspan="2">SGST</th>
                                                <th rowspan="2">Total</th>
                                            </tr>
                                            <tr height="30">
                                                <th>Rate</th>
                                                <th>Amt</th>
                                                <th>Rate</th>
                                                <th>Amt</th>

                                            </tr>

                                        </tbody>
                                    </table>
                                </td>
                            </tr> 
                        </tbody>
                    </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>              
    	