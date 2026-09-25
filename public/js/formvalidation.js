$(document).ready(function () {
    $( "#milmacenter_type" ).change(function() {
        processmilmacenter();
      });
      

      $( "#affiliate").change(function() {
        processaffliation();
      });

      $( "#building").change(function() {
        processbuilding();
      });

      $("#administration").change(function() {
        processadmistration();
      });

      $("#typeofsubdivison").change(function() {
        processSubDivision();
      });

      processmilmacenter();
      processaffliation();
      processbuilding();
      processadmistration();
      processSubDivision();

      $("#region_id").change(function() {
        getDistrictdetails($(this).val());
      });

      $("#taluk").change(function() {
        getVillagedetails($(this).val());
      });

      $("#blockpanchayat").change(function() {
        getgrampanchdetails($(this).val());
      });

      $( "#teamcreation" ).submit(function( event ) {
        var isAllFieldenter = true;
        if($('#administration').val() == 'MANAGINGCOMMITEE'){
            if( $('#adminpresident').val().trim().length < 1 || $('#adminpresidentaddress1').val().trim().length < 1 ||
                $('#adminpresidentaddress2').val().trim().length < 1 || $('#adminpresidentaddress3').val().trim().length < 1 ||
                $('#adminpresidentphonenumber').val().trim().length < 1 || $('#nomemebercommitte').val().trim().length < 1 ||
                $('#dateofelection').val().trim().length < 1
            ){
                 isAllFieldenter = false;
            }
        }

        if($('#administration').val() == 'PARTTIMEADMIN'){
            if( $('#nameparttimepresident').val().trim().length < 1 || $('#parttimedesignation').val().trim().length < 1 ||
                $('#parttimeadminphonenumber').val().trim().length < 1 
            ){
                 isAllFieldenter = false;
            }
        }

        if($('#administration').val() == 'ADMINISTRATIVECOMMIT'){
            if( $('#nameoftheconvenor').val().trim().length < 1 || $('#convenoraddress1').val().trim().length < 1 ||
                $('#convenoraddress2').val().trim().length < 1 || $('#convenoraddress3').val().trim().length < 1 ||
                $('#convenorphonenumber').val().trim().length < 1 || $('#datepostedforlection').val().trim().length < 1 
            ){
                 isAllFieldenter = false;
            }
        }
        if(!isAllFieldenter) {
            alert("Please enter all fileds !!!");
            event.preventDefault();
        }
        
      });


      function processmilmacenter(){
        if($("#milmacenter_type").val() == 'NONAPCOS') {
            $( "#affliatedcontainer" ).hide();
            $( "#affliatednumbercontainer").hide();
            $( "#affiliationdatecontainer").hide();
            $("#milmaapcosprefix").hide();
        } else {
            $( "#affliatedcontainer" ).show();
            $( "#affliatednumbercontainer").show();
            $( "#affiliationdatecontainer").show();
            $("#milmaapcosprefix").show();
        }
      }

      function processaffliation(){
        if($('#affiliate').val() == 'YES') {
            $( "#affliatednumbercontainer" ).show();
            $( "#affiliationdatecontainer" ).show();
        } else {
            $( "#affliatednumbercontainer" ).hide();
            $( "#affiliationdatecontainer" ).hide();
        }
      }

      function processSubDivision(){
        if($('#typeofsubdivison').val() == 'Gramapanchayath') {
            $( "#gramapanchayatcontainer" ).show();
            $( "#muncipalityscontainer" ).hide();
            $( "#cooperationcontainer" ).hide();
            $( "#assemblyconstituencysContainer" ).hide();
        } else if($('#typeofsubdivison').val() == 'Muncipality'){
            $( "#gramapanchayatcontainer" ).hide();
            $( "#muncipalityscontainer" ).show();
            $( "#cooperationcontainer" ).hide();
            $( "#assemblyconstituencysContainer" ).hide();
        } else if($('#typeofsubdivison').val() == 'Coorperation'){
            $( "#gramapanchayatcontainer" ).hide();
            $( "#muncipalityscontainer" ).hide();
            $( "#cooperationcontainer" ).show();
            $( "#assemblyconstituencysContainer" ).hide();
        }else if($('#typeofsubdivison').val() == 'Assemblyconstituency') {
            $( "#gramapanchayatcontainer" ).hide();
            $( "#muncipalityscontainer" ).hide();
            $( "#cooperationcontainer" ).hide();
            $( "#assemblyconstituencysContainer" ).show();
        }else {
            $( "#gramapanchayatcontainer" ).hide();
            $( "#muncipalityscontainer" ).hide();
            $( "#cooperationcontainer" ).hide();
            $( "#assemblyconstituencysContainer" ).hide();
        }
      }

      function processbuilding(){
        if($("#building").val() == 'OWNED') {
            $( "#yearofconstructionContainer" ).show();
        } else {
            $( "#yearofconstructionContainer" ).hide();
        }
      }

      function processadmistration(){
        if($('#administration').val() == 'MANAGINGCOMMITEE') {
            $( "#managescommitcontrol").show();
            $( "#parttimeadministratorcontrol").hide();
            $( "#admincommiteecontrol").hide();
        } else if($('#administration').val() == 'PARTTIMEADMIN'){
            $("#managescommitcontrol").hide();
            $("#parttimeadministratorcontrol").show();
            $("#admincommiteecontrol").hide();
        } else if($('#administration').val() == 'ADMINISTRATIVECOMMIT'){
            $( "#managescommitcontrol") .hide();
            $( "#parttimeadministratorcontrol" ).hide();
            $( "#admincommiteecontrol" ).show();
        }
      }
    
      
});

function getDistrictdetails(regionid){
    $.get( $('#infoservice').val(), { id: regionid, resulttype:"region" })
        .done(function( data ) {
            var region = data.data[0];
            var taluk = region.taluk;
            var blockpa = region.taluk;
            var bmcc = region.bmcc;
            var muncipality = region.muncipality;
            var cooperation = region.cooperation;
            var assemblyconstituency = region.assemblyconstituency;
            populateOptions(taluk,$("#taluk"));
            populateOptions(blockpa,$("#blockpanchayat"));
            populateOptions(bmcc,$("#bmcc"));
            populateOptions(muncipality,$("#muncipality"));
            populateOptions(cooperation,$("#cooperation"));
            populateOptions(assemblyconstituency,$("#assemblyconstituency"));
            
    });
}



function getteamSearch(){
    var regionid = $('#region_id').val();
    var talukid = $('#taluk').val();
    var villageid = $('#village').val();
    var blockpanchayathid = $('#blockpanchayat').val();
    var typeofsubdivisionid = $('#typeofsubdivison').val();
    var gramapanchayatid = $('#gramapanchayat').val();
    var muncipalityid = $('#muncipality').val();
    var cooperationid = $('#cooperation').val();
    var assemblyconstituencyid = $('#assemblyconstituency').val();

    $.get( $('#infoserviceteam').val(), { region_id: regionid, taluk:talukid, village:villageid,  blockpanchayat:blockpanchayathid, 
        typeofsubdivison:typeofsubdivisionid, gramapanchayat:gramapanchayatid, muncipality:muncipalityid, cooperation:cooperationid,
        assemblyconstituency:assemblyconstituencyid
    })
        .done(function( data ) {
            
            var datatable = $( '.datatable-Team' ).DataTable();
            datatable.clear();
            var myData2 = [
                {
                    "Name of Society": 'testing irshad',
                    "Registration Number": "34567",
                    "Address": "testing data"
                }
            ];
            datatable.rows.add(myData2);
            datatable.draw();
            
            
    });
}

function getVillagedetails(talukid){
    $.get( $('#infoservice').val(), { id: talukid, resulttype:"village" })
        .done(function( data ) {
            var village = data.data;
            populateOptions(village,$("#village"));
    });
}

function getgrampanchdetails(blockid){
    $.get( $('#infoservice').val(), { id: blockid, resulttype:"block" })
        .done(function( data ) {
            var grama = data.data;
            populateOptions(grama,$("#gramapanchayat"));
            
    });
}

function populateOptions(data, element){
    //var sel = $("#select");
    
    if(typeof(data) != 'undefined'){
        element.empty();
        element.append('<option value=\'\'>--select--</option>');
        for (var i=0; i<data.length; i++) {
            element.append('<option value="' + data[i].id + '">' + data[i].name + '</option>');
        }
        element.trigger('change');
    }
    
}