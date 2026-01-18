<div class="code-snippet">
    <h3>App/Models/Disabled{{$names["singular_table_name"]}}.php</h3>
    <pre id="disabled_model"><code>
      namespace App\Models;

      use Carbon\Carbon;
      use Illuminate\Database\Eloquent\Factories\HasFactory;
      use Illuminate\Database\Eloquent\Model;

      class Disabled{{$names["singular_model_name"]}} extends Model
      {
          use HasFactory;
          protected $fillable = [
              '{{$names["singular_table_name"]}}_id',
              'business_id',
              'created_by',
          ];

      }

</code></pre>



    <button class="copy-button" onclick="copyToClipboard('disabled_model')">Copy</button>
</div>
