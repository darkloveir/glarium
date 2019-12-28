<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CityPopulation;
use App\Models\UserCity;
use App\Models\Research;
use App\Models\UserResearch;
use App\Models\UserResource;
use App\Models\Message;
use App\User;
use App\Helpers\UserResourceHelper;
use App\Helpers\CombatHelper;
use Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getUserResources()
    {
        UserResourceHelper::updateResources();
        //Obtenemos el total de investigadores
        $cities = UserCity::where('user_id',Auth::id())->pluck('city_id');
        $total_scientists = CityPopulation::whereIn('city_id',$cities)->sum('scientists');
        $data = Auth::user()->resources->only(['gold','trade_ship','trade_ship_available','research_point']);
        $data['total_scientists'] = intval($total_scientists);
        return $data;
    }

    public function config()
    {
        //Devuelve las configuraciones del juego
        $data['world'] = config('world');
        $data['research'] = Research::select(['id','research_category_id as category_id','level','cost'])->get();
        $data['user_research'] = UserResearch::where('user_id',Auth::id())->pluck('research_id');
        return $data;
    }

    public function buyTradeShip()
    {
        UserResourceHelper::updateResources();
        $userResource = UserResource::where('user_id',Auth::id())->first();

        if($userResource->trade_ship==200)
        {
            return 'Alcanzaste el limite maximo de barcos';
        }

        $level = $userResource->trade_ship + 1;
        if($level<10)
        {
            $goldCost = $level*490;
        }
        else
        {
            $coeficiente = ($level/1000)+1.8;
            $goldCost = floor(pow($level,$coeficiente)*(80+($level/10)));
        }

        if($goldCost>$userResource->gold)
        {
            return 'No tienes suficiente oro';
        }

        $userResource->gold -= $goldCost;
        $userResource->trade_ship += 1;
        $userResource->trade_ship_available += 1;
        $userResource->save();

        return 'ok';
    }

    public function sendMessage(Request $request,User $user)
    {
        $request->validate([
            'message' => 'required|string|max:20'
        ]);

        $message = new Message();
        $message->user_from = Auth::id();
        $message->user_to = $user->id;
        $message->message = $request->input('message');
        $message->save();

        return 'ok';
    }
}
