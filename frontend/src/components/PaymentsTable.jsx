import {money} from '../api';
import {Table} from './DataUI';
export default function PaymentsTable({rows}){return <Table rows={rows} columns={[
 {key:'id',label:'#'},{key:'payment_date',label:'Date'},{key:'lot_name',label:'Lot'},
 {key:'amount',label:'Amount',render:r=>money(r.amount)},{key:'method',label:'Method'},{key:'status',label:'Status'},{key:'transaction_id',label:'Reference'}
 ]}/>;}
