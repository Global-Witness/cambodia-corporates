<?php
$gType = isset($_GET['type']) ? safe($_GET['type']): ''; 

$value = '';
switch($gType){
    case 'individual':
        $value= safe($_GET['individual']);
        break;
    case 'company':
        $value= safe($_GET['company']);
        break;
    case 'address':
        $value['house'] = safe($_GET['house']);
        $value['street'] = safe($_GET['street']);
        break;
    case 'ids':
        $value= safe($_GET['id']);
        break;
}
?>
<section class="row" id="search">

    
    <form id="form-search" class="form-switch" method="get" action="/search">
        
        <div class="col-xs-12 col-sm-2 col-md-2">
            <div class="form-group">
                <label class="sr-only" for="type">Search By:</label>
                <select class="form-control selectpicker form-switch switcher" id="type" name="type" title="Search by...">
                    <!-- <option></option> -->
                    <option value="individual" <?php echo ($gType == 'individual' ? 'selected' : ''); ?>>Person</option>
                    <option value="nationality" <?php echo ($gType == 'nationality' ? 'selected' : ''); ?>>Nationality of Chairperson</option>
                    <option value="address" <?php echo ($gType == 'address' ? 'selected' : ''); ?>>Street address and/or house number</option>
                    <option value="company" <?php echo ($gType == 'company' ? 'selected' : ''); ?>>Company</option>
                    <option value="ids" <?php echo ($gType == 'ids' ? 'selected' : ''); ?>>Registration &amp; Amendent IDs</option>
                </select>
                    
            </div>
        </div>
        
        <div class="col-xs-12 col-sm-7 col-md-8">
            <div class="form-group form-switch switchable default" <?php echo (!empty($gType) ? 'style="display: none;"' : ''); ?>>
                <p class="form-control-static"> <strong>Please</strong> select a search filter.</p>
                
            </div>
            
            <div class="form-group form-switch switchable individual" <?php echo ($gType == 'individual' ? 'style="display: block;"' : ''); ?>>
                <label for="individual" class="sr-only">
                    Individual
                </label>
                <div class="form-group name">
                    <input type="text" name="individual" id="individual" class="form-control" placeholder="Individual..." <?php echo ($gType == 'individual' ? 'value="'.$value.'"' : ''); ?> >
                </div>
                <div class="form-group fuzz">
                    <select name="threshold" id="fuzziness" class="form-control selectpicker" title="Fuzziness...">
                        <option value="0" <?php echo (isset($_GET['threshold']) && $_GET['threshold'] == 0 ? ' selected' : ''); ?>>Exact match</option>
                        
                        <option value="parts" <?php echo ( (isset($_GET['threshold']) && $_GET['threshold'] == 'parts' )  ? ' selected' : ''); ?> data-subtext="when the search term is contained within a word">Partial match</option>
                        
                        <option value="1" <?php echo (isset($_GET['threshold']) && $_GET['threshold'] == 1 ? ' selected' : ''); ?>>Up to 1 character difference</option>
                        <option value="2" <?php echo ((isset($_GET['threshold']) && $_GET['threshold'] == 2) || !isset($_GET['threshold']) ? ' selected' : ''); ?>>Up to 2 characters difference</option>
                        <option value="3" <?php echo (isset($_GET['threshold']) && $_GET['threshold'] == 3 ? ' selected' : ''); ?>>Up to 3 characters difference</option>
                        <option value="4" <?php echo (isset($_GET['threshold']) && $_GET['threshold'] == 4 ? ' selected' : ''); ?>>Up to 4 characters difference</option>                        
                    </select>
                </div>
            </div>
            
            <div class="form-group form-switch switchable nationality" <?php echo ($gType == 'nationality' ? 'style="display: block;"' : ''); ?>>
                <label for="nationality" class="sr-only">
                    Nationality
                </label>
                <div class="input-group">
                    <select name="nationality" id="nationality" class="form-control selectpicker" title="Select nationality..." data-live-search="true" data-live-search-placeholder="Search for a nationality...">
                        <?php include('inc-nationalities.php'); ?>
                    </select>
                    <span class="input-group-addon" data-toggle="tooltip" data-placement="bottom" title="This list was extracted directly from the Ministry of Commerce’s dataset and has not been altered."><i class="fa fa-question-circle"></i></span>
                </div> 
            </div>
            
            <div class="form-group form-switch switchable address" <?php echo ($gType == 'address' ? 'style="display: block;"' : ''); ?>>
                <div class="form-group house">
                    <input class="form-control " type="text" name="house" value="<?php echo (isset($_GET['house']) && !empty($_GET['house']) ? $value['house'] : ''); ?>" placeholder="House # (optional)" >
                </div>
                <div class="form-group street">
                    <input class="form-control " type="text" name="street" value="<?php echo (isset($_GET['street']) && !empty($_GET['street']) ? $value['street'] : ''); ?>" placeholder="Street name" >
                </div>
            </div>
            
            <div class="form-group form-switch switchable company"  <?php echo ($gType == 'company' ? 'style="display: block;"' : ''); ?>>
                <label for="company" class="sr-only">
                    Search key
                </label>
                <input type="text" name="company" id="company" class="form-control" placeholder="Company name..." <?php echo ($gType == 'company' ? 'value="'.$value.'"' : ''); ?> >
            </div>
            
            
            
            <div class="form-group form-switch switchable ids" <?php echo ($gType == 'ids' ? 'style="display: block;"' : ''); ?>>
                <label for="ids" class="sr-only">
                    Search key
                </label>
                <input type="text" name="id" id="id" class="form-control" placeholder="Registration or Amendment ID..." <?php echo ($gType == 'ids' ? 'value="'.$value.'"' : ''); ?> >
            </div>
            
        </div>
        
        
        <div class="col-xs-12 col-sm-3 col-md-2">      
            <div class="form-group">
                <input type="hidden" name="search" value="submitted">
                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Search</button>
                
            </div>
        </div>
        
        
    </form>
   

</section>


