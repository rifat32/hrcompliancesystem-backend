<div class="code-snippet">
    <h3>database/migrations/2024_07_26_182431_create_{{ $names["table_name"] }}_table_.php</h3>
    <pre id="migration"><code>

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class Create{{ $names["plural_model_name"] }}Table extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('{{ $names["table_name"] }}', function (Blueprint $table) {
                $table->id();

                @foreach ($fields->toArray() as $field)

                @if ($field["is_foreign_key"])

                $table->foreignId("{{$field['name']}}")
                {{($field["basic_validation_rule"] == "required" || $field["basic_validation_rule"] == "filled")? "":"->nullable()"}}
                ->constrained("{{$field['relationship_table_name']}}")
                ->onDelete("cascade");

                @else

                $table->{{$field["db_validation_type"]}}("{{$field['name']}}"{{$field["db_validation_type"]=="decimal"?",10,2":""}}){{($field["basic_validation_rule"] == "required" || $field["basic_validation_rule"] == "filled")? "":"->nullable()"}};
                @endif



                @endforeach



                @if ($is_active)
                $table->boolean("is_active")->default(false);

                @if ($is_default)
                $table->boolean("is_default")->default(false);
                @endif

                @endif




                $table->foreignId("business_id")
                ->constrained("businesses")
                ->onDelete("cascade");

                $table->unsignedBigInteger("created_by");
                $table->softDeletes();
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::dropIfExists('{{ $names["table_name"] }}');
        }
    }



</code></pre>
    <button class="copy-button" onclick="copyToClipboard('migration')">Copy</button>
</div>
