<?php

namespace App\Http\Controllers;
use App\Models\Purchasesitem;
use App\Models\Purchasesbill;
use App\Models\rawmaterials;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Helper;

class PurchasesitemsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $salesbill = Purchasesbill::find($request->id);
        // print($salesbill->id);die();
        if($salesbill->status == 1):
            $rules = ["material"=>"required|numeric","quant"=>"required|numeric|min:1|max:999999|regex:/^(([0-9]*)(\.([0-9]+))?)$/","price"=>"required|numeric|min:1|max:999999|regex:/^(([0-9]*)(\.([0-9]+))?)$/"];
            $message = ["product.required"=>"يجب ادخال الصنف","quant.required"=>"الكمية يجب ان لاتكوم فارغة",
            "quant.required"=>"الكمية يجب ان لاتكوم فارغة","price.required"=>"يجب ادخال السعر"];
            $request->validate($rules,$message);
            // echo $salesbill->id;die();
            $id = Purchasesitem::create([
            "purchases_id"=>$salesbill->id,
            'rawmati'=>$request->material,
            "qoun"=>$request->quant,
            "total"=>($request->quant*$request->price),
            "descont"=>0,
            "user_id"=>Auth::id()])->id;
            $raw = rawmaterials::find($request->material);
            $raw->quantity = ($raw->quantity + $request->quant);
            $raw->price = $request->price;
            $raw->update();
            echo 1;
            Helper::Collect_purbill($salesbill->id);
        else:
            echo "الفاتورة مغلقة";
        endif;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function edit_item($id)
    {
        // die();
        $salesItem = Purchasesitem::find($id);
        // die();
        $salesbill = Purchasesbill::find($salesItem->purchases_id);
        if($salesbill->status == 1){
        $data = array(
            "type"=>1,
            "mate" => $salesItem->rawmati,
            "price" => ($salesItem->total + $salesItem->descont)/$salesItem->qoun,
            "qoun"=>$salesItem->qoun,
            "total"=>$salesItem->total+$salesItem->descont,
            // "descont"=>$salesItem->descont
        );
        

        $salesItem->delete();
        Helper::Collect_purbill($salesbill->id);
        }else{
            $data=array("type"=>2,"massege"=>"الفتورة مغلقة");
            
        }
        echo json_encode($data);
        
    }

    public function getItemTotal($id)
    {
        //
        $bill = Purchasesbill::find($id);
        $data = Purchasesitem::join("rawmaterials","rawmaterials.id","=","purchases_items.rawmati")->
        select("rawmaterials.material_name","purchases_items.*")->where("purchases_items.purchases_id",$id)->get();
        $total = array("total"=>$bill->tolal,"sincere"=>$bill->sincere,"Residual"=>$bill->Residual,"tbody"=>"");
        foreach($data as $val){
            $total['tbody'] .= "<tr >
            <td>".$val->id."</td>
            <td>".$val->material_name."</td>
            <td>".$val->qoun."</td>
            <td>".$val->descont."</td>
            <td>".$val->total."</td>
            <td>".$val->created_at."</td>
            <td class='d-flex justify-content-end'>
                    <button class='btn btn-info ml-1 btn-icon dele' id='".$val->id."'><i class='mdi mdi-delete'></i></button>
                    <button class='btn btn-danger btn-icon edit-item' id='".$val->id."'><i class='mdi mdi-transcribe'></i></button>
                </td>
            </tr>";
        }
        echo json_encode($total);
    }
    public function destroy($id)
    {
        $salesItem = Purchasesitem::find($id);
        $salesbill = Purchasesbill::find($salesItem->purchases_id);
        if($salesbill->status == 1){
        // Helper::add_from_mate($salesItem->id);
        $raw = rawmaterials::find($salesItem->rawmati);
        // print_r($salesItem->raw);die();
        $raw->quantity = ($raw->quantity - $salesItem->qoun);
        $raw->update();
        $salesItem->delete();
        Helper::Collect_purbill($salesbill->id);
        echo 1;
    }else{
        echo "الفاتورة مغلقة";
    }
    }
}
