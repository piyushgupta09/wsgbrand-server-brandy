<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use App\Models\User;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Prody\Models\ProductOption;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Brandy\Http\Resources\ChatResource;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Requests\ChatCreateRequest;
use Fpaipl\Panel\Notifications\WebPushNotification;
use Fpaipl\Brandy\Http\Resources\LedgerChatResource;

class ChatCoordinator extends Coordinator
{
    public function show(Request $request, $ledger_sid)
    {
        $perPage = 50;
    
        $ledger = Ledger::where('sid', $ledger_sid)->first();
    
        if (!$ledger) {
            return ApiResponse::error('Ledger not found', 404); // Or any other appropriate response
        }

        // request may have search, start_date, end_date, so consider these if available

        $search = $request->search;
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        if ($search) {
            $chats = Chat::where('ledger_id', $ledger->id)
                         ->where('content', 'like', '%' . $search . '%')
                         ->with('orders','readies','demands','adjustments','dispatches','purchases')
                         ->orderBy('created_at', 'desc')
                         ->paginate($perPage);
        // } else if ($start_date && $end_date) {
        //     $chats = Chat::where('ledger_id', $ledger->id)
        //                  ->whereBetween('created_at', [$start_date, $end_date])
        //                  ->with('orders','readies','demands','adjustments','dispatches','purchases')
        //                  ->orderBy('created_at', 'desc')
        //                  ->paginate($perPage);
        } else {
            $chats = Chat::where('ledger_id', $ledger->id)
                         ->with('orders','readies','demands','adjustments','dispatches','purchases')
                         ->orderBy('created_at', 'desc')
                         ->paginate($perPage);
        }

        $chats->setCollection($chats->getCollection()->reverse());

        return ApiResponse::success([
            'chats' => LedgerChatResource::collection($chats),
            'ledger' => [
                'name' => $ledger->name,
                'image' => $ledger->getImage(ProductOption::MEDIA_CONVERSION_BANNER),
                // 'stock' => new ShowProductResource($ledger->stock),
            ],
            'pagination' => [
                'total' => $chats->total(),
                'perPage' => $chats->perPage(),
                'currentPage' => $chats->currentPage(),
                'lastPage' => $chats->lastPage(),
            ],
        ]);
    }    
    
    public function store(ChatCreateRequest $request)
    {
        try{
            $ledger = Ledger::where('sid', $request->ledger_sid)->first();
            
            $chat = Chat::createChatIfNecessary($request, $ledger, true);

            $title = $request->user()->name . ' sent a message.';
            $message = 'New message in ' . $ledger->name . ' ledger.';

            $senderIsBrand = $request->user()->isBrand();
            $senderIsParty = $request->user()->isParty();
            if ($senderIsBrand) {
                $action = 'ledgers/chats/' . $ledger->sid;
                $ledger->party->user->notify(new WebPushNotification($title, $message, $action));
            } elseif ($senderIsParty) {
                $brandManagers = User::whereHas('roles', function ($query) {
                    $query->where('name', 'manager-brand');
                })->get();
                // send notification to all brand managers
                foreach ($brandManagers as $brandManager) {
                    $action = 'products/chats/' . $ledger->sid;
                    $brandManager->notify(new WebPushNotification($title, $message, $action));
                }
            }        

            return ApiResponse::success(new ChatResource($chat));

            // ReloadDataEvent::dispatch(CHAT::NEW_CHAT_EVENT . '#' . $ledger->sid);
        } catch(\Exception $e){
            return ApiResponse::error($e->getMessage(), 404);
        }
    }

}
