use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\UrgentActionsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Admin API routes
Route::middleware(['auth:sanctum', 'role:admin,super-admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/urgent-actions', [UrgentActionsController::class, 'index'])
            ->name('api.admin.urgent-actions');
    }); 