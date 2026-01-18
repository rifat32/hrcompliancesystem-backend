
    <div class="col-md-2">
        <div class="form-group">
            <label for="field_name" class="form-label">Field Name</label>
            <input type="text" class="form-control" id="field_name" name="field_names[]">
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label for="basic_validation_rules" class="control-label">Basic Validation Rules:</label>
            <select class="form-control select2" id="basic_validation_rules" name="basic_validation_rules[]">
                <optgroup label="Basic Validation Rules">
                    @foreach($validationRules['Basic Validation Rules'] as $rule)
                        <option value="{{ $rule }}">{{ $rule }}</option>
                    @endforeach
                </optgroup>
            </select>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label for="validation_type" class="control-label">Validation Type:</label>
            <select class="form-control select2" id="validation_type" name="validation_types[]" onchange="showValidationRules(this.value, this)">
                <option value="">Select Type</option>
                <option value="string">String</option>
                <option value="numeric">Numeric</option>

                <option value="array">Array</option>
                <option value="boolean">Boolean</option>
            </select>
        </div>
    </div>
    <div class="col-md-2" id="string-validation-rules" style="display: none;">
        <div class="form-group">
            <label for="string_validation_rules" class="control-label">String Validation Rules:</label>
            <select class="form-control select2" id="string_validation_rules" name="string_validation_rules[]">
                <optgroup label="String Validation Rules">
                    @foreach($validationRules['String Validation Rules'] as $rule)
                        <option value="{{ $rule }}">{{ $rule }}</option>
                    @endforeach
                </optgroup>
            </select>
        </div>
    </div>

    <div class="col-md-2" id="number-validation-rules" style="display: none;">
        <div class="form-group">
            <label for="number_validation_rules" class="control-label">Number Validation Rules:</label>
            <select class="form-control select2" id="number_validation_rules" name="number_validation_rules[]">
                <optgroup label="Number Validation Rules">
                    @foreach($validationRules['Numeric Validation Rules'] as $rule)
                        <option value="{{ $rule }}">{{ $rule }}</option>
                    @endforeach
                </optgroup>
            </select>
        </div>
    </div>

    <div class="col-md-2" id="is-unique" style="display: none;" >
        <div class="form-group">
            <label for="is_unique" class="control-label">Unique:</label>
            <select name="is_unique_values[]" id="is_unique">
                <option value="1">Yes</option>
                <option value="0" selected>No</option>
            </select>
        </div>
    </div>

    <div class="col-md-2" id="is-foreign-key" style="display: none;">
        <div class="form-group">
            <label for="is_foreign_key" class="control-label">Foreign Key:</label>
            <select name="is_foreign_key_values[]" id="is_foreign_key" onchange="showRelationshipTable(this.value, this)">
                <option value="1">Yes</option>
                <option value="0" selected>No</option>
            </select>
        </div>
    </div>



    <div class="col-md-2" id="relationship-table-name" style="display: none;">
        <div class="form-group">
            <label for="relationship_table_name" class="form-label">Relationship Table Name</label>
            <input type="text" class="form-control" id="relationship_table_name" name="relationship_table_names[]">
        </div>
    </div>



