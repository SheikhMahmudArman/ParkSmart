import {Table} from './DataUI';
export default function SessionsTable({rows,actions}){return <Table rows={rows} actions={actions} columns={[
 {key:'id',label:'#'},{key:'plate_number',label:'Vehicle'},{key:'lot_name',label:'Lot'},{key:'space_number',label:'Space'},{key:'entry_time',label:'Entry (Dhaka)'}
 ]}/>;}
