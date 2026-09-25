import { Link } from "react-router-dom";
import { useAuth } from "../../context/AuthContext";
import { useData, Notice, Panel } from "../../components/DataUI";

export default function Dashboard() {
  const { user } = useAuth(),
    state = useData(`/users/${user.id}/reservations`),
    rows = state.data?.reservations || [];

  return (
    <Panel title={`Welcome, ${user.name}`}>
      <Notice {...state} />
      <p>
        {rows.length} reservations ·{" "}
        {rows.filter((r) => r.status === "Active").length} active sessions ·{" "}
        {
          rows.filter(
            (r) =>
              r.payment_status !== "Paid" &&
              !["Cancelled", "Expired"].includes(r.status),
          ).length
        }{" "}
        unpaid bookings
      </p>
      
      <div className="d-flex gap-3 flex-wrap">
        <Link className="btn btn-primary" to="/driver/search">
          Find parking
        </Link>
        <Link className="btn btn-outline-info" to="/driver/profile">
          Manage vehicles
        </Link>
        <Link className="btn btn-outline-info" to="/driver/reservations">
          My reservations
        </Link>
      </div>
    </Panel>
  );
}
