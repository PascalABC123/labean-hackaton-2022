<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Support\Facades\Auth;

class ItemsController extends Controller
{
    public function filter($items, $type)
    {
        $sort = session()->get('sort');
        $category = session()->get('category');
        $currency = session()->get('currency');
        $keywords = session()->get('keywords');

        if ($sort == null) return $items->orderBy('id', 'desc');

        if ($keywords != null) {
            $items = $items->where('id', '-1');
            foreach(explode(' ', $keywords) as $keyword) {
                $items = $items->orWhere(function ($query) use ($keyword, $type) {
                    $query->where('type', $type)->where('description', 'like', '%' . $keyword . '%');
                });
                $items = $items->orWhere(function ($query) use ($keyword, $type) {
                    $query->where('type', $type)->where('city', 'like', '%' . $keyword . '%');
                });
            }
        }

        switch ($sort) {
            case 'later':
                $items = $items->orderBy('id', 'desc');
                break;
            case 'earlier':
                $items = $items->orderBy('id', 'asc');
                break;
            case 'more':
                $items = $items->orderBy('amount', 'desc');
                break;
            case 'less':
                $items = $items->orderBy('amount', 'asc');
                break;
        }

        if ($category != 'default') {
            $items = $items->where('category_id', $category);
        }

        if ($currency != 'any') {
            switch ($currency) {
                case "RUB":
                    $items = $items->where('currency', 'RUB');
                    break;
                case "USD":
                    $items = $items->where('currency', 'USD');
                    break;
                case "CNY":
                    $items = $items->where('currency', 'CNY');
                    break;
            }
        }

        session()->remove('sort');
        session()->remove('category');
        session()->remove('currency');
        session()->remove('keywords');

        return $items;
    }

    public function getBuyItems()
    {
        $items = Item::with('category', 'user')->where('type', 'buy');
        $items = $this->filter($items, 'buy');
        return view('sell', ['items' => $items->get()]);
    }

    public function getSellItems()
    {
        $items = Item::with('category', 'user')->where('type', 'sell');
        $items = $this->filter($items, 'sell');
        return view('buy', ['items' => $items->get()]);
    }

    public function openRequestSell($id)
    {
        return view('requestsell', ['item' => Item::findOrFail($id)]);
    }

    public function openRequestBuy($id)
    {
        return view('requestbuy', ['item' => Item::findOrFail($id)]);
    }

    public function storeBuy()
    {
        $attributes = request()->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['required', 'string', 'min:10', 'max:1024'],
            'price' => ['required'],
            'currency' => ['required', 'string'],
            'amount' => ['required', 'integer'],
            'city' => ['required', 'string', 'max:32'],
        ]);
        $attributes['type'] = 'buy';
        $attributes['user_id'] = Auth::id();
        Item::create($attributes);

        return redirect('/sell');
    }

    public function storeSell()
    {
        $attributes = request()->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['required', 'string', 'min:10', 'max:1024'],
            'price' => ['required'],
            'currency' => ['required', 'string'],
            'amount' => ['required', 'integer'],
            'city' => ['required', 'string', 'max:32'],
        ]);
        $attributes['type'] = 'sell';
        $attributes['user_id'] = Auth::id();
        Item::create($attributes);

        return redirect('/sell');
    }

    public function setFilters()
    {
        session()->start();
        session()->flash('sort', request()->get('sort'));
        session()->flash('category', request()->get('category'));
        session()->flash('currency', request()->get('currency'));
        session()->flash('keywords', request()->get('keywords'));
        return redirect()->back();
    }
}
