import {money} from '../../api';
import {useData,Notice,Panel,Table} from '../../components/DataUI';
export default function Reports(){
 const state=useData('/reports/revenue'),bookings=useData('/reports/reservations-by-lot'),spending=useData('/reports/spending-by-driver');
 return <><Panel title="Revenue and occupancy"><Notice {...state}/>{state.data&&<p>Revenue this month: {money(state.data.monthly_revenue)} · All reservations: {state.data.total_reservations} · Physical occupancy: {state.data.occupancy}%</p>}
 <Table rows={state.data?.revenue_by_lot||[]} columns={[{key:'lot',label:'Lot'},{key:'amount',label:'All-time revenue',render:r=>money(r.amount)},{key:'transactions',label:'Payments'},{key:'average',label:'Average',render:r=>money(r.average)}]}/></Panel>
 <Panel title="Reservations by lot"><Notice {...bookings}/><Table rows={bookings.data||[]} columns={[{key:'lot_name',label:'Lot'},{key:'total_reservations',label:'All reservations'}]}/></Panel>
 <Panel title="Spending by driver"><Notice {...spending}/><Table rows={spending.data||[]} columns={[{key:'driver_name',label:'Driver'},{key:'total_payments',label:'Payments'},{key:'total_spent',label:'Total',render:r=>money(r.total_spent)}]}/></Panel></>;
}
