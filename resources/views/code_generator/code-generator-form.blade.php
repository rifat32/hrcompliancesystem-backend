<div class="container">
    <h1 class="text-center mt-5">Code Generation Form</h1>
    <form method="POST" action="{{ route('code-generator') }}">
        @csrf
        <div class="mb-3">
            <label for="table_name" class="form-label">Database Table Name</label>
            <input type="text" class="form-control" id="table_name"
               name="table_name" value="{{!empty($names["table_name"])?$names["table_name"]:''}}">
        </div>

        <div class="row mb-5">
            <h3>Default Fields</h3>
            <div class="row"  >
                <div class="col-md-2" id="is-default"  >
                    <div class="form-group">
                        <label for="is_default" class="control-label">Is Default:</label>
                        <select name="is_default" id="is_default">
                            <option value="1" selected>Yes</option>
                            <option value="0" >No</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2" id="is-default"  >
                    <div class="form-group">
                        <label for="is_active" class="control-label">Is Active:</label>
                        <select name="is_active" id="is_active">
                            <option value="1" selected>Yes</option>
                            <option value="0" >No</option>
                        </select>
                    </div>
                </div>

            </div>

        </div>



        <div class="row">
            <h3>Other Fields</h3>
            <div class="row mb-5" id="default-field-container" style="display: none;">
                @include('code_generator.field_container')
            </div>
            <div class="row mb-5" id="field-container">
                @include('code_generator.field_container')
            </div>
        </div>


        <button type="button" class="btn btn-primary" id="add-more">Add More</button>






        <button type="submit" class="btn btn-primary">Generate Code</button>
    </form>
</div>



{{-- <script>
  function showValidationRules(value) {
    var stringRules = document.getElementById('string-validation-rules');
    var numberRules = document.getElementById('number-validation-rules');


    if (value =='string') {
        stringRules.style.display = 'block';
        numberRules.style.display = 'none';
    } else if (value == 'number') {
        stringRules.style.display = 'none';
        numberRules.style.display = 'block';
    } else {
        stringRules.style.display = 'none';
        numberRules.style.display = 'none';

    }
}

</script> --}}





<script>
    document.addEventListener('DOMContentLoaded', function() {
        var fieldContainer = document.getElementById('default-field-container');
        var addMoreButton = document.getElementById('add-more');

        addMoreButton.addEventListener('click', function() {
            var newRow = fieldContainer.innerHTML;
            var newDiv = document.createElement('div');
            newDiv.className = 'row';
            newDiv.innerHTML = newRow;
            fieldContainer.parentNode.appendChild(newDiv);
        });
    });

    function showValidationRules(value, element) {
        console.log(element.parentNode.parentNode)
        var validationType = value;

        var stringValidationRules = element.parentNode.parentNode.parentNode.querySelector('#string-validation-rules');

        var numberValidationRules = element.parentNode.parentNode.parentNode.querySelector('#number-validation-rules');

        var isForeignKey = element.parentNode.parentNode.parentNode.querySelector('#is-foreign-key');

        var isUnique = element.parentNode.parentNode.parentNode.querySelector('#is-unique');

        var relationshipTableName = element.parentNode.parentNode.parentNode.querySelector('#relationship-table-name');




        if (validationType =='string') {
            stringValidationRules.style.display = 'block';
            numberValidationRules.style.display = 'none';
            isUnique.style.display = 'block';
            isForeignKey.style.display = 'none';
            relationshipTableName.style.display = 'none';

        } else if (validationType == 'numeric') {
            stringValidationRules.style.display = 'none';
            numberValidationRules.style.display = 'block';
            isUnique.style.display = 'none';
            isForeignKey.style.display = 'block';





        } else {
            stringValidationRules.style.display = 'none';
            numberValidationRules.style.display = 'none';
            isUnique.style.display = 'none';
            isForeignKey.style.display = 'none';
            relationshipTableName.style.display = 'none';

        }
    }

    function showRelationshipTable(value, element) {
        console.log(element.parentNode.parentNode.parentNode.parentNode.parentNode)

        var isForeignKey = value;

        var relationshipTableName = element.parentNode.parentNode.parentNode.querySelector('#relationship-table-name');

        console.log(relationshipTableName)

            if(isForeignKey == 1) {
                relationshipTableName.style.display = 'block';
            }
         else {
            relationshipTableName.style.display = 'none';
        }
    }

</script>
