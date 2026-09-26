import { useAuth } from "../../context/AuthContext";
import { api, money } from "../../api";
import {
  useData,
  useAction,
  ActionNotice,
  Notice,
  Panel,
  Table,
} from "../../components/DataUI";
import PaymentsTable from "../../components/PaymentsTable";
export default function Payment() {
  const { user } = useAuth(),
    state = useData(`/users/${user.id}/payments`),
    fines = useData(`/users/${user.id}/finds`);
  const action = useAction(async () => {
    await Promise.all([state.reload(), fines.reload()]);
  });
  return (
    <>
      <Panel title="Payment history">
        <p>
          Course demonstration only. No real card or bank payments are
          processed.
        </p>
        <Notice {...state} />
        <ActionNotice {...action} />
        <PaymentsTable rows={state.data || []} />
      </Panel>
      <Panel title="Overstay fines">
        <Notice {...fines} />
        <Table
          rows={fines.data || []}
          columns={[
            { key: "id", label: "#" },
            { key: "reason", label: "Reason" },
            { key: "amount", label: "Amount", render: (r) => money(r.amount) },
            { key: "status", label: "Status" },
          ]}
          actions={(r) =>
            r.status === "Pending" && (
              <button
                className="btn btn-sm btn-success"
                disabled={action.busy}
                onClick={() =>
                  action.run(
                    () =>
                      api(`/finds/${r.id}/pay`, {
                        method: "POST",
                        body: { payment_method: "Demo" },
                      }),
                    "Demo fine payment recorded",
                  )
                }
              >
                Pay demo
              </button>
            )
          }
        />
      </Panel>
    </>
  );
}
