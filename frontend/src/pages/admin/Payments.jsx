import { useData, Notice, Panel } from "../../components/DataUI";
import PaymentsTable from "../../components/PaymentsTable";

export default function Payments() {
  const state = useData("/payments");

  return (
    <Panel title="All demo payments">
      <Notice {...state} />
      <PaymentsTable rows={state.data || []} />
    </Panel>
  );
}
